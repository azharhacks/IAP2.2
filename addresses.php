<?php
/**
 * Address Management Page
 * Allows users to view, add, edit, and delete their addresses
 */

session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ClassAutoload.php';

// Check if user is logged in and 2FA is verified
if (!isset($_SESSION['user_id'])) {
    header('Location: Signin.php?redirect=addresses.php');
    exit();
}

if (!isset($_SESSION['2fa_verified']) || $_SESSION['2fa_verified'] !== true) {
    header('Location: database/2fa_verify.php?redirect=addresses.php');
    exit();
}

// Initialize database connection
try {
    $dsn = "mysql:host={$conf['db_host']};dbname={$conf['db_name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $conf['db_user'], $conf['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$userId = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add_address':
                case 'edit_address':
                    $firstName = trim($_POST['first_name'] ?? '');
                    $lastName = trim($_POST['last_name'] ?? '');
                    $company = trim($_POST['company'] ?? '');
                    $addressType = $_POST['address_type'] ?? 'home';
                    $addressLine1 = trim($_POST['address_line_1'] ?? '');
                    $addressLine2 = trim($_POST['address_line_2'] ?? '');
                    $city = trim($_POST['city'] ?? '');
                    $state = trim($_POST['state'] ?? '');
                    $county = trim($_POST['county'] ?? '');
                    $postalCode = trim($_POST['postal_code'] ?? '');
                    $country = trim($_POST['country'] ?? 'Kenya');
                    $phone = trim($_POST['phone'] ?? '');
                    $isDefault = isset($_POST['is_default']) ? 1 : 0;
                    
                    // Validation
                    if (empty($firstName) || empty($lastName) || empty($addressLine1) || empty($city) || empty($phone)) {
                        throw new Exception("First name, last name, address, city, and phone are required.");
                    }
                    
                    if (!preg_match('/^[\+]?[0-9\s\-\(\)]{10,15}$/', $phone)) {
                        throw new Exception("Please enter a valid phone number.");
                    }
                    
                    // If setting as default, remove default from other addresses
                    if ($isDefault) {
                        $stmt = $pdo->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?");
                        $stmt->execute([$userId]);
                    }
                    
                    if ($_POST['action'] === 'add_address') {
                        // Insert new address
                        $stmt = $pdo->prepare("
                            INSERT INTO addresses (user_id, first_name, last_name, company, address_type, 
                                                 address_line_1, address_line_2, city, state, county, 
                                                 postal_code, country, phone, is_default)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $userId, $firstName, $lastName, $company, $addressType,
                            $addressLine1, $addressLine2, $city, $state, $county,
                            $postalCode, $country, $phone, $isDefault
                        ]);
                        $success = "Address added successfully!";
                    } else {
                        // Update existing address
                        $addressId = (int)$_POST['address_id'];
                        $stmt = $pdo->prepare("
                            UPDATE addresses 
                            SET first_name = ?, last_name = ?, company = ?, address_type = ?,
                                address_line_1 = ?, address_line_2 = ?, city = ?, state = ?, county = ?,
                                postal_code = ?, country = ?, phone = ?, is_default = ?, updated_at = NOW()
                            WHERE id = ? AND user_id = ?
                        ");
                        $stmt->execute([
                            $firstName, $lastName, $company, $addressType,
                            $addressLine1, $addressLine2, $city, $state, $county,
                            $postalCode, $country, $phone, $isDefault, $addressId, $userId
                        ]);
                        $success = "Address updated successfully!";
                    }
                    break;
                    
                case 'delete_address':
                    $addressId = (int)$_POST['address_id'];
                    
                    // Check if it's the default address
                    $stmt = $pdo->prepare("SELECT is_default FROM addresses WHERE id = ? AND user_id = ?");
                    $stmt->execute([$addressId, $userId]);
                    $address = $stmt->fetch();
                    
                    if ($address && $address['is_default']) {
                        // Check if there are other addresses
                        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM addresses WHERE user_id = ? AND id != ? AND is_active = 1");
                        $stmt->execute([$userId, $addressId]);
                        $count = $stmt->fetch()['count'];
                        
                        if ($count > 0) {
                            // Set another address as default
                            $stmt = $pdo->prepare("SELECT id FROM addresses WHERE user_id = ? AND id != ? AND is_active = 1 LIMIT 1");
                            $stmt->execute([$userId, $addressId]);
                            $nextAddress = $stmt->fetch();
                            
                            if ($nextAddress) {
                                $stmt = $pdo->prepare("UPDATE addresses SET is_default = 1 WHERE id = ?");
                                $stmt->execute([$nextAddress['id']]);
                            }
                        }
                    }
                    
                    // Soft delete
                    $stmt = $pdo->prepare("UPDATE addresses SET is_active = 0 WHERE id = ? AND user_id = ?");
                    $stmt->execute([$addressId, $userId]);
                    
                    $success = "Address deleted successfully!";
                    break;
                    
                case 'set_default':
                    $addressId = (int)$_POST['address_id'];
                    
                    // Remove default from all addresses
                    $stmt = $pdo->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?");
                    $stmt->execute([$userId]);
                    
                    // Set new default
                    $stmt = $pdo->prepare("UPDATE addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
                    $stmt->execute([$addressId, $userId]);
                    
                    $success = "Default address updated successfully!";
                    break;
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Fetch user addresses
try {
    $stmt = $pdo->prepare("
        SELECT * FROM addresses 
        WHERE user_id = ? AND is_active = 1 
        ORDER BY is_default DESC, created_at DESC
    ");
    $stmt->execute([$userId]);
    $addresses = $stmt->fetchAll();
} catch (Exception $e) {
    $addresses = [];
    $error = "Error fetching addresses: " . $e->getMessage();
}

// Create layout instance
$layout = new Layout();

// Custom CSS for addresses page
$customCSS = '
.address-card {
    border: none;
    border-radius: var(--border-radius);
    box-shadow: var(--card-shadow);
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: var(--backdrop-blur);
    transition: var(--transition);
}

.address-card:hover {
    box-shadow: var(--card-shadow-hover);
    transform: translateY(-2px);
}

.address-card.default {
    border-left: 4px solid var(--primary-gradient);
}

.address-actions {
    position: absolute;
    top: 1rem;
    right: 1rem;
}

.address-actions .dropdown-toggle::after {
    display: none;
}

.form-section {
    background: rgba(255, 255, 255, 0.9);
    border-radius: var(--border-radius);
    padding: 2rem;
    box-shadow: var(--card-shadow);
}
';

$layout->header('My Addresses', $customCSS);
$layout->navbar('addresses');

// Breadcrumb
$layout->breadcrumb(['My Addresses']);

$layout->contentStart();
?>

<div class="container py-4">
    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-gradient mb-0">My Addresses</h2>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addressModal" onclick="openAddressModal()">
                    <i class="fas fa-plus me-2"></i>Add New Address
                </button>
            </div>
        </div>
    </div>

    <?php if (empty($addresses)): ?>
    <div class="row">
        <div class="col-12">
            <div class="text-center py-5">
                <i class="fas fa-map-marker-alt text-muted" style="font-size: 4rem;"></i>
                <h4 class="mt-3 text-muted">No addresses found</h4>
                <p class="text-muted mb-4">Add your first address to get started with deliveries.</p>
                <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#addressModal" onclick="openAddressModal()">
                    <i class="fas fa-plus me-2"></i>Add Address
                </button>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="row">
        <?php foreach ($addresses as $address): ?>
        <div class="col-lg-6 mb-4">
            <div class="card address-card <?php echo $address['is_default'] ? 'default' : ''; ?> position-relative">
                <div class="card-body p-4">
                    <!-- Address Actions Dropdown -->
                    <div class="address-actions">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" 
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="#" onclick="openAddressModal(<?php echo htmlspecialchars(json_encode($address)); ?>)">
                                        <i class="fas fa-edit me-2"></i>Edit
                                    </a>
                                </li>
                                <?php if (!$address['is_default']): ?>
                                <li>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="set_default">
                                        <input type="hidden" name="address_id" value="<?php echo $address['id']; ?>">
                                        <button type="submit" class="dropdown-item">
                                            <i class="fas fa-star me-2"></i>Set as Default
                                        </button>
                                    </form>
                                </li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this address?')">
                                        <input type="hidden" name="action" value="delete_address">
                                        <input type="hidden" name="address_id" value="<?php echo $address['id']; ?>">
                                        <button type="submit" class="dropdown-item text-danger">
                                            <i class="fas fa-trash me-2"></i>Delete
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Default Badge -->
                    <?php if ($address['is_default']): ?>
                    <div class="mb-2">
                        <span class="badge bg-primary">Default Address</span>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Address Information -->
                    <h5 class="card-title mb-3">
                        <?php echo htmlspecialchars($address['first_name'] . ' ' . $address['last_name']); ?>
                        <?php if ($address['company']): ?>
                            <small class="text-muted">@ <?php echo htmlspecialchars($address['company']); ?></small>
                        <?php endif; ?>
                    </h5>
                    
                    <div class="address-details">
                        <p class="mb-2">
                            <i class="fas fa-map-marker-alt text-primary me-2"></i>
                            <?php echo htmlspecialchars($address['address_line_1']); ?>
                            <?php if ($address['address_line_2']): ?>
                                <br><span class="ms-4"><?php echo htmlspecialchars($address['address_line_2']); ?></span>
                            <?php endif; ?>
                        </p>
                        
                        <p class="mb-2">
                            <i class="fas fa-city text-primary me-2"></i>
                            <?php echo htmlspecialchars($address['city']); ?>
                            <?php if ($address['county']): ?>
                                , <?php echo htmlspecialchars($address['county']); ?>
                            <?php endif; ?>
                            <?php if ($address['state']): ?>
                                , <?php echo htmlspecialchars($address['state']); ?>
                            <?php endif; ?>
                        </p>
                        
                        <?php if ($address['postal_code']): ?>
                        <p class="mb-2">
                            <i class="fas fa-mail-bulk text-primary me-2"></i>
                            <?php echo htmlspecialchars($address['postal_code']); ?>
                        </p>
                        <?php endif; ?>
                        
                        <p class="mb-2">
                            <i class="fas fa-flag text-primary me-2"></i>
                            <?php echo htmlspecialchars($address['country']); ?>
                        </p>
                        
                        <p class="mb-0">
                            <i class="fas fa-phone text-primary me-2"></i>
                            <?php echo htmlspecialchars($address['phone']); ?>
                        </p>
                        
                        <small class="text-muted mt-2 d-block">
                            <i class="fas fa-home me-1"></i>
                            <?php echo ucfirst($address['address_type']); ?> Address
                        </small>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Address Modal -->
<div class="modal fade" id="addressModal" tabindex="-1" aria-labelledby="addressModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addressModalLabel">Add New Address</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="addressForm" data-validate="true">
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add_address">
                    <input type="hidden" name="address_id" id="addressId" value="">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">First Name *</label>
                            <input type="text" class="form-control" name="first_name" id="modal_first_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Last Name *</label>
                            <input type="text" class="form-control" name="last_name" id="modal_last_name" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="company" class="form-label">Company (Optional)</label>
                        <input type="text" class="form-control" name="company" id="modal_company">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="address_type" class="form-label">Address Type</label>
                            <select class="form-select" name="address_type" id="modal_address_type">
                                <option value="home">Home</option>
                                <option value="work">Work</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone Number *</label>
                            <input type="tel" class="form-control" name="phone" id="modal_phone" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address_line_1" class="form-label">Address Line 1 *</label>
                        <input type="text" class="form-control" name="address_line_1" id="modal_address_line_1" 
                               placeholder="Street address, building name, apartment number" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address_line_2" class="form-label">Address Line 2 (Optional)</label>
                        <input type="text" class="form-control" name="address_line_2" id="modal_address_line_2" 
                               placeholder="Apartment, suite, unit, building, floor, etc.">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="city" class="form-label">City *</label>
                            <input type="text" class="form-control" name="city" id="modal_city" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="county" class="form-label">County</label>
                            <select class="form-select" name="county" id="modal_county">
                                <option value="">Select County</option>
                                <option value="Nairobi">Nairobi</option>
                                <option value="Mombasa">Mombasa</option>
                                <option value="Kisumu">Kisumu</option>
                                <option value="Nakuru">Nakuru</option>
                                <option value="Eldoret">Eldoret</option>
                                <option value="Thika">Thika</option>
                                <option value="Malindi">Malindi</option>
                                <option value="Kitale">Kitale</option>
                                <option value="Garissa">Garissa</option>
                                <option value="Kakamega">Kakamega</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="state" class="form-label">State/Region</label>
                            <input type="text" class="form-control" name="state" id="modal_state">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="postal_code" class="form-label">Postal Code</label>
                            <input type="text" class="form-control" name="postal_code" id="modal_postal_code">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="country" class="form-label">Country</label>
                        <select class="form-select" name="country" id="modal_country">
                            <option value="Kenya">Kenya</option>
                            <option value="Tanzania">Tanzania</option>
                            <option value="Uganda">Uganda</option>
                            <option value="Rwanda">Rwanda</option>
                            <option value="Burundi">Burundi</option>
                        </select>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_default" id="modal_is_default">
                        <label class="form-check-label" for="modal_is_default">
                            Set as default address
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-save me-2"></i>Save Address
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAddressModal(address = null) {
    const modal = document.getElementById('addressModal');
    const form = document.getElementById('addressForm');
    const modalTitle = document.getElementById('addressModalLabel');
    const submitBtn = document.getElementById('submitBtn');
    
    if (address) {
        // Edit mode
        modalTitle.textContent = 'Edit Address';
        submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Update Address';
        document.getElementById('formAction').value = 'edit_address';
        document.getElementById('addressId').value = address.id;
        
        // Populate form fields
        document.getElementById('modal_first_name').value = address.first_name || '';
        document.getElementById('modal_last_name').value = address.last_name || '';
        document.getElementById('modal_company').value = address.company || '';
        document.getElementById('modal_address_type').value = address.address_type || 'home';
        document.getElementById('modal_phone').value = address.phone || '';
        document.getElementById('modal_address_line_1').value = address.address_line_1 || '';
        document.getElementById('modal_address_line_2').value = address.address_line_2 || '';
        document.getElementById('modal_city').value = address.city || '';
        document.getElementById('modal_county').value = address.county || '';
        document.getElementById('modal_state').value = address.state || '';
        document.getElementById('modal_postal_code').value = address.postal_code || '';
        document.getElementById('modal_country').value = address.country || 'Kenya';
        document.getElementById('modal_is_default').checked = address.is_default == 1;
    } else {
        // Add mode
        modalTitle.textContent = 'Add New Address';
        submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Save Address';
        document.getElementById('formAction').value = 'add_address';
        document.getElementById('addressId').value = '';
        form.reset();
        document.getElementById('modal_country').value = 'Kenya';
    }
    
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
}
</script>

<?php
$layout->contentEnd();
$layout->footer();
?>
