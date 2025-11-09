-- ===============================================
-- COMPLETE E-COMMERCE DATABASE SCHEMA
-- Amazon-style Marketplace Database
-- ===============================================

-- Enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- ===============================================
-- 1. USERS TABLE (Already exists, keeping current structure)
-- Stores user account information
-- ===============================================
-- Users table structure from your existing setup:
-- id, username, email, password, verification_token, email_verified, verified, totp_secret, token_expiry, created_at

-- ===============================================
-- 2. USER PROFILES TABLE
-- Extended user information for delivery and preferences
-- ===============================================
CREATE TABLE IF NOT EXISTS user_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    phone VARCHAR(20),
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other'),
    profile_picture VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ===============================================
-- 3. ADDRESSES TABLE
-- Multiple delivery addresses per user
-- ===============================================
CREATE TABLE IF NOT EXISTS addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    address_type ENUM('home', 'work', 'other') DEFAULT 'home',
    address_line_1 VARCHAR(255) NOT NULL,
    address_line_2 VARCHAR(255),
    city VARCHAR(100) NOT NULL,
    county VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100) DEFAULT 'Kenya',
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ===============================================
-- 4. CATEGORIES TABLE
-- Product categorization system
-- ===============================================
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    parent_id INT NULL, -- For subcategories
    image_url VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- ===============================================
-- 5. BRANDS TABLE
-- Product brands/manufacturers
-- ===============================================
CREATE TABLE IF NOT EXISTS brands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    logo_url VARCHAR(255),
    website VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ===============================================
-- 6. PRODUCTS TABLE
-- Main product catalog
-- ===============================================
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    short_description VARCHAR(500),
    sku VARCHAR(100) UNIQUE,
    category_id INT NOT NULL,
    brand_id INT,
    price DECIMAL(10,2) NOT NULL,
    compare_price DECIMAL(10,2), -- Original price for discounts
    cost_price DECIMAL(10,2), -- For profit calculation
    stock_quantity INT DEFAULT 0,
    min_stock_level INT DEFAULT 5,
    weight DECIMAL(8,2), -- in kg
    dimensions VARCHAR(100), -- LxWxH in cm
    is_digital BOOLEAN DEFAULT FALSE,
    requires_shipping BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    meta_title VARCHAR(255),
    meta_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL,
    INDEX idx_category (category_id),
    INDEX idx_brand (brand_id),
    INDEX idx_featured (is_featured),
    INDEX idx_active (is_active),
    INDEX idx_price (price)
);

-- ===============================================
-- 7. PRODUCT IMAGES TABLE
-- Multiple images per product
-- ===============================================
CREATE TABLE IF NOT EXISTS product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255),
    sort_order INT DEFAULT 0,
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- ===============================================
-- 8. PRODUCT ATTRIBUTES TABLE
-- Dynamic product specifications (color, size, etc.)
-- ===============================================
CREATE TABLE IF NOT EXISTS product_attributes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    attribute_name VARCHAR(100) NOT NULL,
    attribute_value VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product_attr (product_id, attribute_name)
);

-- ===============================================
-- 9. SHOPPING CART TABLE
-- User's shopping cart items
-- ===============================================
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL, -- Price at time of adding to cart
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_product (user_id, product_id)
);

-- ===============================================
-- 10. ORDERS TABLE
-- Main order information
-- ===============================================
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    
    -- Order totals
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    shipping_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(10,2) NOT NULL,
    
    -- Order status
    status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded') DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    payment_method VARCHAR(50),
    
    -- Shipping information
    shipping_address TEXT NOT NULL,
    shipping_method VARCHAR(100),
    tracking_number VARCHAR(100),
    estimated_delivery DATE,
    
    -- Order notes and timestamps
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    shipped_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_orders (user_id),
    INDEX idx_status (status),
    INDEX idx_payment_status (payment_status),
    INDEX idx_order_date (created_at)
);

-- ===============================================
-- 11. ORDER ITEMS TABLE
-- Individual products in each order
-- ===============================================
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL, -- Snapshot of product name
    product_sku VARCHAR(100),
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- ===============================================
-- 12. ORDER STATUS HISTORY TABLE
-- Track order status changes
-- ===============================================
CREATE TABLE IF NOT EXISTS order_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    comment TEXT,
    notify_customer BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT, -- Admin user who made the change
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- ===============================================
-- 13. REVIEWS TABLE
-- Product reviews and ratings
-- ===============================================
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    order_item_id INT, -- Link to specific purchase
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(255),
    review_text TEXT,
    is_verified_purchase BOOLEAN DEFAULT FALSE,
    is_approved BOOLEAN DEFAULT TRUE,
    helpful_votes INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE SET NULL,
    UNIQUE KEY unique_user_product_review (user_id, product_id)
);

-- ===============================================
-- 14. WISHLIST TABLE
-- User's saved products for later
-- ===============================================
CREATE TABLE IF NOT EXISTS wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_product_wishlist (user_id, product_id)
);

-- ===============================================
-- 15. COUPONS TABLE
-- Discount coupons and promotional codes
-- ===============================================
CREATE TABLE IF NOT EXISTS coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('percentage', 'fixed_amount') NOT NULL,
    value DECIMAL(10,2) NOT NULL,
    minimum_amount DECIMAL(10,2) DEFAULT 0.00,
    maximum_discount DECIMAL(10,2),
    usage_limit INT,
    used_count INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    starts_at TIMESTAMP,
    expires_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ===============================================
-- SAMPLE DATA INSERTION
-- ===============================================

-- Insert sample categories
INSERT IGNORE INTO categories (name, description, image_url) VALUES
('Electronics', 'Mobile phones, laptops, and electronic devices', 'https://via.placeholder.com/300x200/007bff/ffffff?text=Electronics'),
('Computers', 'Laptops, desktops, and computer accessories', 'https://via.placeholder.com/300x200/28a745/ffffff?text=Computers'),
('Mobile Phones', 'Smartphones and mobile accessories', 'https://via.placeholder.com/300x200/dc3545/ffffff?text=Mobile+Phones'),
('Audio', 'Headphones, speakers, and audio equipment', 'https://via.placeholder.com/300x200/ffc107/333333?text=Audio'),
('Home & Office', 'Printers, office supplies, and home electronics', 'https://via.placeholder.com/300x200/17a2b8/ffffff?text=Home+Office');

-- Insert sample brands
INSERT IGNORE INTO brands (name, description, logo_url) VALUES
('Safaricom', 'Leading telecommunications company in Kenya', 'https://via.placeholder.com/150x50/00ff00/ffffff?text=Safaricom'),
('Tecno', 'Popular smartphone and laptop brand', 'https://via.placeholder.com/150x50/0066cc/ffffff?text=Tecno'),
('Infinix', 'Affordable smartphones with great features', 'https://via.placeholder.com/150x50/ff6600/ffffff?text=Infinix'),
('Oraimo', 'Audio and mobile accessories brand', 'https://via.placeholder.com/150x50/ff0099/ffffff?text=Oraimo'),
('Samsung', 'Global electronics manufacturer', 'https://via.placeholder.com/150x50/1428a0/ffffff?text=Samsung'),
('HP', 'Computer and printer manufacturer', 'https://via.placeholder.com/150x50/0073e6/ffffff?text=HP');

-- Insert sample products
INSERT IGNORE INTO products (name, description, short_description, sku, category_id, brand_id, price, compare_price, stock_quantity, is_featured) VALUES
('Safaricom Neon Storm', 'Latest 4G smartphone with M-Pesa integration, 6.5" display, 48MP camera, 4000mAh battery, Android 13. Perfect for Kenyan users with built-in mobile money features.', 'Latest 4G smartphone with M-Pesa integration and premium features', 'SAF-NEON-001', 3, 1, 45000.00, 52000.00, 25, TRUE),
('Tecno MegaBook T1', 'Affordable laptop perfect for students and professionals. Intel Core i5, 8GB RAM, 256GB SSD, 15.6" Full HD display, Windows 11. Excellent performance for productivity.', 'Perfect for students and professionals with powerful performance', 'TEC-MEGA-001', 2, 2, 85000.00, 95000.00, 15, TRUE),
('Oraimo FreePods 4', 'Wireless earbuds with crystal clear sound, active noise cancellation, 6-hour battery life, IPX5 water resistance. Compatible with all smartphones.', 'Wireless earbuds with crystal clear sound and long battery life', 'ORA-FREE-004', 4, 4, 8500.00, 12000.00, 50, TRUE),
('Samsung 55" Smart TV', '4K UHD Smart TV with Netflix, YouTube, and more streaming apps. HDR support, voice control, multiple HDMI ports. Transform your entertainment experience.', '4K UHD Smart TV with Netflix, YouTube and more streaming apps', 'SAM-TV55-001', 1, 5, 125000.00, 145000.00, 8, TRUE),
('Infinix Note 30 Pro', 'Gaming smartphone with MediaTek Helio G99, 108MP camera, 120W fast charging, 6.78" AMOLED display. Perfect for mobile gaming and photography.', 'Gaming smartphone with fast charging and amazing camera', 'INF-NOTE-030', 3, 3, 32000.00, 38000.00, 30, TRUE),
('HP DeskJet 2720e', 'All-in-one wireless printer perfect for home and office use. Print, scan, copy, wireless connectivity, mobile printing support, affordable ink cartridges.', 'All-in-one wireless printer perfect for home and office use', 'HP-DESK-2720', 5, 6, 15500.00, 18000.00, 20, TRUE);

-- Insert sample product images
INSERT IGNORE INTO product_images (product_id, image_url, alt_text, sort_order, is_primary) VALUES
(1, 'https://via.placeholder.com/400x400/667eea/ffffff?text=Safaricom+Neon+Storm', 'Safaricom Neon Storm Front View', 1, TRUE),
(1, 'https://via.placeholder.com/400x400/667eea/ffffff?text=Safaricom+Back', 'Safaricom Neon Storm Back View', 2, FALSE),
(2, 'https://via.placeholder.com/400x400/764ba2/ffffff?text=Tecno+MegaBook', 'Tecno MegaBook T1', 1, TRUE),
(3, 'https://via.placeholder.com/400x400/28a745/ffffff?text=Oraimo+FreePods', 'Oraimo FreePods 4', 1, TRUE),
(4, 'https://via.placeholder.com/400x400/ffc107/333333?text=Samsung+TV', 'Samsung 55 inch Smart TV', 1, TRUE),
(5, 'https://via.placeholder.com/400x400/dc3545/ffffff?text=Infinix+Note', 'Infinix Note 30 Pro', 1, TRUE),
(6, 'https://via.placeholder.com/400x400/17a2b8/ffffff?text=HP+Printer', 'HP DeskJet 2720e', 1, TRUE);

-- Insert sample product attributes
INSERT IGNORE INTO product_attributes (product_id, attribute_name, attribute_value) VALUES
(1, 'Color', 'Storm Blue'),
(1, 'Storage', '128GB'),
(1, 'RAM', '6GB'),
(1, 'Screen Size', '6.5 inches'),
(2, 'Processor', 'Intel Core i5'),
(2, 'RAM', '8GB'),
(2, 'Storage', '256GB SSD'),
(2, 'Screen Size', '15.6 inches'),
(3, 'Color', 'Black'),
(3, 'Connectivity', 'Bluetooth 5.2'),
(3, 'Battery Life', '6 hours'),
(4, 'Screen Size', '55 inches'),
(4, 'Resolution', '4K UHD'),
(4, 'Smart Features', 'Netflix, YouTube, Browser'),
(5, 'Color', 'Cosmic Black'),
(5, 'Storage', '256GB'),
(5, 'Camera', '108MP Triple Camera'),
(6, 'Type', 'All-in-One'),
(6, 'Connectivity', 'WiFi, USB');

COMMIT;
