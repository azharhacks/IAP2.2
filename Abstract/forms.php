
<?php
require __DIR__ . '/../Config.php';
require __DIR__ . '/../ClassAutoload.php';


class Forms {

    public function signup($conf) {
        global $conf;

        // Connect to database using socket (XAMPP)
        $socket_path = '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock';
        if (file_exists($socket_path)) {
            $dsn = "mysql:unix_socket={$socket_path};dbname={$conf['db_name']};charset=utf8mb4";
        } else {
            $dsn = "mysql:host={$conf['db_host']};dbname={$conf['db_name']};charset=utf8mb4";
        }
        $pdo = new PDO($dsn, $conf['db_user'], $conf['db_pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Handle form submission
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $username = trim($_POST['username']);
            $email    = trim($_POST['email']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password

            // Check if email already exists
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $checkStmt->execute([$email]);
            
            if ($checkStmt->rowCount() > 0) {
                echo "<p style='color:red;'>Error: Email already exists. Please use a different email.</p>";
            } else {
                // Generate verification token and TOTP secret
                $verificationToken = bin2hex(random_bytes(32));
                $tokenExpiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
                
                // Generate TOTP secret for 2FA
                require_once __DIR__ . '/../vendor/autoload.php';
                $tfa = new RobThree\Auth\TwoFactorAuth();
                $totpSecret = $tfa->createSecret();

                // Insert into DB with email_verified = 0
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, email_verified, verification_token, token_expiry, totp_secret) VALUES (?, ?, ?, 0, ?, ?, ?)");
                if ($stmt->execute([$username, $email, $password, $verificationToken, $tokenExpiry, $totpSecret])) {
                    // Send verification email
                    require_once __DIR__ . '/../Mail/SendMail.php';
                    $ObjSendMail = new SendMail();
                    
                    if ($ObjSendMail->sendVerificationMail($conf, $username, $email, $verificationToken)) {
                        echo "<p style='color:green;'>Signup successful! Please check your email to verify your account before signing in.</p>";
                    } else {
                        echo "<p style='color:orange;'>Account created but verification email could not be sent. Please contact support.</p>";
                    }
                } else {
                    echo "<p style='color:red;'>Error: could not sign up.</p>";
                }
            }
        }
        

?>
        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" name="username" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" name="email" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" class="form-control" name="password" required>
            </div>

            <?php $this->submit_button('Sign Up', 'signup'); ?>
            <a href='signin.php'>Already have an account? Sign In</a>
        </form>
<?php
    }

    public function signin($conf) {
        global $conf;

        // Handle form submission
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            // Connect to database using socket (XAMPP)
            $socket_path = '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock';
            if (file_exists($socket_path)) {
                $dsn = "mysql:unix_socket={$socket_path};dbname={$conf['db_name']};charset=utf8mb4";
            } else {
                $dsn = "mysql:host={$conf['db_host']};dbname={$conf['db_name']};charset=utf8mb4";
            }
            $pdo = new PDO($dsn, $conf['db_user'], $conf['db_pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $email = trim($_POST['email']);
            $password = $_POST['password'];

            // Check user credentials and verification status
            $stmt = $pdo->prepare("SELECT id, username, password, email_verified FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                if ($user['email_verified'] == 1) {
                    // User is verified, set session for 2FA verification
                    session_start();
                    $_SESSION['pending_2fa_user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $email;
                    
                    // Redirect to 2FA verification
                    header("Location: 2fa_verify.php");
                    exit();
                } else {
                    echo "<p style='color:red;'>Please verify your email address before signing in. Check your email for the verification link.</p>";
                }
            } else {
                echo "<p style='color:red;'>Invalid email or password.</p>";
            }
        }
?>
        <form method="POST" action="">
            <div class="mb-3">
                <label for="exampleInputEmail1" class="form-label">Email address</label>
                <input type="email" class="form-control" id="exampleInputEmail1" name="email" required>
            </div>
            <div class="mb-3">
                <label for="exampleInputPassword1" class="form-label">Password</label>
                <input type="password" class="form-control" id="exampleInputPassword1" name="password" required>
            </div>

            <?php $this->submit_button('Sign In', 'signin'); ?>
            <a href='signup.php'>Don't have an account? Sign Up</a>
        </form>
<?php
    }

    public function submit_button($text, $name) {
        echo "<button type='submit' class='btn btn-primary' name='{$name}'>{$text}</button>";
    }
}
