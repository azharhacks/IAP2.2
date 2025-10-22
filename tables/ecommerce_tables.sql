-- =====================================================
-- E-COMMERCE DATABASE TABLES FOR KENYA
-- Run these commands in phpMyAdmin SQL tab
-- =====================================================

-- 1. PRODUCTS TABLE
-- Store all your products (phones, laptops, accessories, etc.)
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL, -- KSh prices with 2 decimal places
    category_id INT,
    brand VARCHAR(100),
    image_url VARCHAR(500),
    stock_quantity INT DEFAULT 0,
    status ENUM('active', 'inactive', 'out_of_stock') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. CATEGORIES TABLE
-- Organize products by categories
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    parent_id INT DEFAULT NULL, -- For subcategories
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. ORDERS TABLE
-- Store customer orders
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    order_number VARCHAR(50) UNIQUE NOT NULL, -- e.g., ORD-001
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    payment_method ENUM('mpesa', 'card', 'bank_transfer', 'cash_on_delivery') DEFAULT 'mpesa',
    shipping_address TEXT,
    phone_number VARCHAR(15),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 4. ORDER_ITEMS TABLE
-- Store individual items within each order
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL, -- Price at time of order
    total DECIMAL(10,2) NOT NULL, -- quantity * price
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- 5. CUSTOMERS TABLE (Additional customer info beyond users table)
CREATE TABLE customers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    phone VARCHAR(15),
    county VARCHAR(50), -- Kenyan counties
    town VARCHAR(100),
    address TEXT,
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 6. CART TABLE
-- Store items in user's shopping cart
CREATE TABLE cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_product (user_id, product_id)
);

-- 7. REVIEWS TABLE
-- Customer product reviews
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- INSERT SAMPLE DATA
-- =====================================================

-- Insert Categories
INSERT INTO categories (name, description) VALUES 
('Smartphones', 'Mobile phones and accessories'),
('Laptops', 'Laptops and computer accessories'),
('Audio', 'Headphones, earbuds, and speakers'),
('Accessories', 'Phone cases, chargers, and other accessories');

-- Insert Sample Products
INSERT INTO products (name, description, price, category_id, brand, stock_quantity, status) VALUES 
('Safaricom Smartphone', 'Latest 4G smartphone with M-Pesa integration', 45000.00, 1, 'Safaricom', 25, 'active'),
('Tecno Laptop Pro', 'Affordable laptop perfect for students and professionals', 85000.00, 2, 'Tecno', 10, 'active'),
('Oraimo FreePods', 'Wireless earbuds with excellent sound quality', 8500.00, 3, 'Oraimo', 50, 'active'),
('Samsung Galaxy A54', 'Mid-range smartphone with great camera', 55000.00, 1, 'Samsung', 15, 'active'),
('HP Pavilion 15', 'Intel Core i5 laptop for business use', 95000.00, 2, 'HP', 8, 'active'),
('JBL Clip 4', 'Portable Bluetooth speaker', 12000.00, 3, 'JBL', 30, 'active');

-- Insert Sample Customers (linking to existing users)
INSERT INTO customers (user_id, first_name, last_name, phone, county, town, address) VALUES 
(1, 'James', 'Mwangi', '0712345678', 'Nairobi', 'Nairobi', 'Kilimani, Nairobi'),
(1, 'Grace', 'Njeri', '0723456789', 'Kiambu', 'Thika', 'Thika Town'),
(1, 'Peter', 'Kimani', '0734567890', 'Mombasa', 'Mombasa', 'Nyali, Mombasa');

-- Insert Sample Orders
INSERT INTO orders (user_id, order_number, total_amount, status, payment_method, shipping_address, phone_number) VALUES 
(1, 'ORD-001', 45000.00, 'shipped', 'mpesa', 'Kilimani, Nairobi', '0712345678'),
(1, 'ORD-002', 85000.00, 'processing', 'mpesa', 'Thika Town', '0723456789'),
(1, 'ORD-003', 8500.00, 'pending', 'mpesa', 'Nyali, Mombasa', '0734567890');

-- Insert Order Items
INSERT INTO order_items (order_id, product_id, quantity, price, total) VALUES 
(1, 1, 1, 45000.00, 45000.00), -- Safaricom phone
(2, 2, 1, 85000.00, 85000.00), -- Tecno laptop
(3, 3, 1, 8500.00, 8500.00);   -- Oraimo earbuds

-- =====================================================
-- USEFUL QUERIES FOR YOUR DASHBOARD
-- =====================================================

-- Get total revenue
-- SELECT SUM(total_amount) as total_revenue FROM orders WHERE payment_status = 'paid';

-- Get total orders
-- SELECT COUNT(*) as total_orders FROM orders;

-- Get total products
-- SELECT COUNT(*) as total_products FROM products WHERE status = 'active';

-- Get recent orders with customer details
-- SELECT o.order_number, CONCAT(c.first_name, ' ', c.last_name) as customer_name, 
--        o.total_amount, o.status, o.created_at
-- FROM orders o 
-- JOIN customers c ON o.user_id = c.user_id 
-- ORDER BY o.created_at DESC LIMIT 10;
 
-- Get low stock products
-- SELECT name, stock_quantity FROM products WHERE stock_quantity < 10 AND status = 'active';

-- =====================================================
-- INDEXES FOR BETTER PERFORMANCE
-- =====================================================

-- Add indexes for frequently queried columns
CREATE INDEX idx_products_category ON products(category_id);
CREATE INDEX idx_products_status ON products(status);
CREATE INDEX idx_orders_user ON orders(user_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_order_items_order ON order_items(order_id);
CREATE INDEX idx_order_items_product ON order_items(product_id);
