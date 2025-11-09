-- ===============================================
-- ADDITIONAL PRODUCTS FOR E-COMMERCE STORE
-- Kenyan Market Focus with Popular Items
-- ===============================================

-- Insert additional products
INSERT IGNORE INTO products (name, description, short_description, sku, category_id, brand_id, price, compare_price, stock_quantity, is_featured) VALUES

-- Electronics & Mobile Phones
('Samsung Galaxy A54 5G', 'Premium mid-range smartphone with 50MP triple camera, 6.4" Super AMOLED display, 5000mAh battery, 128GB storage, 8GB RAM. Perfect for photography and daily use.', 'Premium 5G smartphone with amazing camera and long battery life', 'SAM-A54-128', 3, 5, 58000.00, 65000.00, 20, TRUE),

('iPhone 13 128GB', 'Apple iPhone 13 with A15 Bionic chip, 6.1" Super Retina XDR display, dual-camera system, 5G capability. The most advanced dual-camera system ever on iPhone.', 'Latest iPhone with A15 Bionic chip and advanced dual-camera system', 'APL-IP13-128', 3, 1, 125000.00, 135000.00, 12, TRUE),

('Redmi Note 12 Pro', 'Xiaomi Redmi Note 12 Pro with 108MP camera, 6.67" AMOLED display, 5000mAh battery, 67W fast charging. Exceptional performance at an affordable price.', 'High-performance smartphone with 108MP camera and fast charging', 'XIA-RN12-PRO', 3, 2, 42000.00, 48000.00, 35, TRUE),

-- Laptops & Computers
('Dell Inspiron 15 3000', 'Budget-friendly laptop with Intel Core i3, 4GB RAM, 1TB HDD, 15.6" HD display, Windows 11. Perfect for students and basic computing needs.', 'Affordable laptop perfect for students and basic computing', 'DEL-INS-3000', 2, 3, 65000.00, 72000.00, 18, FALSE),

('MacBook Air M2', 'Apple MacBook Air with M2 chip, 8GB RAM, 256GB SSD, 13.6" Liquid Retina display. Incredibly thin, light, and powerful for professional work.', 'Ultra-thin laptop with M2 chip for professional work', 'APL-MBA-M2', 2, 1, 185000.00, 200000.00, 8, TRUE),

('Lenovo ThinkPad E14', 'Business laptop with AMD Ryzen 5, 8GB RAM, 256GB SSD, 14" FHD display. Built for productivity and reliability in professional environments.', 'Professional business laptop with AMD Ryzen processor', 'LEN-TP-E14', 2, 4, 95000.00, 105000.00, 15, FALSE),

-- Audio & Accessories
('Sony WH-CH720N', 'Wireless noise-canceling headphones with 35-hour battery life, quick charge, multipoint connection. Premium sound quality for music lovers.', 'Wireless noise-canceling headphones with 35-hour battery', 'SON-WH720N', 4, 6, 18500.00, 22000.00, 25, TRUE),

('JBL Flip 6', 'Portable Bluetooth speaker with powerful JBL Original Pro Sound, IP67 waterproof, 12 hours of playtime. Perfect for outdoor adventures.', 'Waterproof portable Bluetooth speaker with powerful sound', 'JBL-FLIP6', 4, 7, 12500.00, 15000.00, 30, TRUE),

('Oraimo FreePods Pro', 'Premium wireless earbuds with active noise cancellation, 8-hour battery life, wireless charging case, IPX5 water resistance.', 'Premium wireless earbuds with active noise cancellation', 'ORA-FP-PRO', 4, 4, 15500.00, 19000.00, 40, TRUE),

-- Home & Office
('Canon PIXMA G3020', 'All-in-one ink tank printer with Wi-Fi, print, scan, copy functions. High page yield and low cost per page for home and office use.', 'All-in-one ink tank printer with Wi-Fi connectivity', 'CAN-G3020', 5, 8, 28000.00, 32000.00, 12, FALSE),

('TP-Link Archer C80', 'AC1900 dual-band Wi-Fi router with 4 Gigabit Ethernet ports, MU-MIMO technology. Fast and reliable internet for homes and offices.', 'High-speed dual-band Wi-Fi router for homes and offices', 'TPL-C80', 5, 9, 8500.00, 10500.00, 20, FALSE),

('Logitech MX Master 3S', 'Advanced wireless mouse with ultra-fast scrolling, customizable buttons, works on any surface including glass. Perfect for professionals.', 'Advanced wireless mouse with ultra-fast scrolling', 'LOG-MX3S', 5, 10, 16500.00, 19000.00, 25, FALSE),

-- Smart TVs & Entertainment
('LG 43" 4K Smart TV', '43-inch 4K UHD Smart TV with webOS, Netflix, YouTube, Prime Video built-in. HDR10 support and AI-powered picture processing.', '43-inch 4K Smart TV with webOS and streaming apps', 'LG-43UK6300', 1, 11, 68000.00, 75000.00, 10, TRUE),

('TCL 32" Smart Android TV', '32-inch HD Smart Android TV with Google Play Store, Chromecast built-in, Netflix, YouTube. Affordable smart TV for any room.', 'Affordable 32-inch Smart Android TV with Google services', 'TCL-32S6500', 1, 12, 35000.00, 40000.00, 15, FALSE),

-- Gaming & Accessories
('PlayStation 5 Controller', 'DualSense wireless controller with haptic feedback, adaptive triggers, built-in microphone. Enhanced gaming experience.', 'Next-gen wireless controller with haptic feedback', 'SON-PS5-CTL', 1, 6, 8500.00, 10000.00, 20, FALSE),

('Xbox Wireless Controller', 'Xbox Wireless Controller with improved D-pad, textured grips, Bluetooth connectivity. Compatible with Xbox and PC.', 'Wireless controller with improved design and Bluetooth', 'MSF-XBX-CTL', 1, 13, 7500.00, 9000.00, 18, FALSE),

-- Power & Charging
('Anker PowerCore 10000', 'Portable charger with 10,000mAh capacity, fast charging technology, compact design. Charge your devices on the go.', 'Compact 10,000mAh portable charger with fast charging', 'ANK-PC10K', 1, 14, 4500.00, 5500.00, 50, FALSE),

('Belkin 3-in-1 Wireless Charger', 'Wireless charging station for iPhone, Apple Watch, and AirPods. Declutter your desk with one charging solution.', 'All-in-one wireless charging station for Apple devices', 'BEL-3IN1', 1, 15, 12000.00, 15000.00, 15, FALSE);

-- Insert additional brands if they don't exist
INSERT IGNORE INTO brands (name, description, logo_url) VALUES
('Apple', 'American multinational technology company', 'https://via.placeholder.com/150x50/000000/ffffff?text=Apple'),
('Xiaomi', 'Chinese electronics company known for smartphones', 'https://via.placeholder.com/150x50/ff6900/ffffff?text=Xiaomi'),
('Dell', 'American computer technology company', 'https://via.placeholder.com/150x50/007db8/ffffff?text=Dell'),
('Lenovo', 'Chinese computer and smartphone company', 'https://via.placeholder.com/150x50/e2231a/ffffff?text=Lenovo'),
('Sony', 'Japanese electronics and entertainment company', 'https://via.placeholder.com/150x50/000000/ffffff?text=Sony'),
('JBL', 'American audio equipment manufacturer', 'https://via.placeholder.com/150x50/f47b00/ffffff?text=JBL'),
('Canon', 'Japanese imaging and optical products company', 'https://via.placeholder.com/150x50/cc0000/ffffff?text=Canon'),
('TP-Link', 'Chinese networking products manufacturer', 'https://via.placeholder.com/150x50/4285f4/ffffff?text=TP-Link'),
('Logitech', 'Swiss computer peripherals company', 'https://via.placeholder.com/150x50/00b8fc/ffffff?text=Logitech'),
('LG', 'South Korean electronics company', 'https://via.placeholder.com/150x50/a50034/ffffff?text=LG'),
('TCL', 'Chinese electronics company', 'https://via.placeholder.com/150x50/1f4788/ffffff?text=TCL'),
('Microsoft', 'American technology corporation', 'https://via.placeholder.com/150x50/00bcf2/ffffff?text=Microsoft'),
('Anker', 'Chinese electronics company specializing in charging', 'https://via.placeholder.com/150x50/0066cc/ffffff?text=Anker'),
('Belkin', 'American consumer electronics company', 'https://via.placeholder.com/150x50/000000/ffffff?text=Belkin');

-- Insert product images for new products
INSERT IGNORE INTO product_images (product_id, image_url, alt_text, sort_order, is_primary) VALUES
-- Samsung Galaxy A54 5G (assuming product_id 7)
(7, 'https://via.placeholder.com/400x400/1f4788/ffffff?text=Galaxy+A54', 'Samsung Galaxy A54 5G', 1, TRUE),
-- iPhone 13 (assuming product_id 8)
(8, 'https://via.placeholder.com/400x400/000000/ffffff?text=iPhone+13', 'Apple iPhone 13', 1, TRUE),
-- Redmi Note 12 Pro (assuming product_id 9)
(9, 'https://via.placeholder.com/400x400/ff6900/ffffff?text=Redmi+Note+12', 'Xiaomi Redmi Note 12 Pro', 1, TRUE),
-- Dell Inspiron (assuming product_id 10)
(10, 'https://via.placeholder.com/400x400/007db8/ffffff?text=Dell+Inspiron', 'Dell Inspiron 15 3000', 1, TRUE),
-- MacBook Air M2 (assuming product_id 11)
(11, 'https://via.placeholder.com/400x400/000000/ffffff?text=MacBook+Air', 'Apple MacBook Air M2', 1, TRUE),
-- Lenovo ThinkPad (assuming product_id 12)
(12, 'https://via.placeholder.com/400x400/e2231a/ffffff?text=ThinkPad', 'Lenovo ThinkPad E14', 1, TRUE),
-- Sony Headphones (assuming product_id 13)
(13, 'https://via.placeholder.com/400x400/000000/ffffff?text=Sony+WH720N', 'Sony WH-CH720N Headphones', 1, TRUE),
-- JBL Speaker (assuming product_id 14)
(14, 'https://via.placeholder.com/400x400/f47b00/ffffff?text=JBL+Flip+6', 'JBL Flip 6 Speaker', 1, TRUE),
-- Oraimo FreePods Pro (assuming product_id 15)
(15, 'https://via.placeholder.com/400x400/ff0099/ffffff?text=FreePods+Pro', 'Oraimo FreePods Pro', 1, TRUE),
-- Canon Printer (assuming product_id 16)
(16, 'https://via.placeholder.com/400x400/cc0000/ffffff?text=Canon+G3020', 'Canon PIXMA G3020', 1, TRUE),
-- TP-Link Router (assuming product_id 17)
(17, 'https://via.placeholder.com/400x400/4285f4/ffffff?text=TP-Link+C80', 'TP-Link Archer C80', 1, TRUE),
-- Logitech Mouse (assuming product_id 18)
(18, 'https://via.placeholder.com/400x400/00b8fc/ffffff?text=MX+Master+3S', 'Logitech MX Master 3S', 1, TRUE),
-- LG TV (assuming product_id 19)
(19, 'https://via.placeholder.com/400x400/a50034/ffffff?text=LG+43+TV', 'LG 43 inch 4K Smart TV', 1, TRUE),
-- TCL TV (assuming product_id 20)
(20, 'https://via.placeholder.com/400x400/1f4788/ffffff?text=TCL+32+TV', 'TCL 32 inch Smart TV', 1, TRUE),
-- PS5 Controller (assuming product_id 21)
(21, 'https://via.placeholder.com/400x400/000000/ffffff?text=PS5+Controller', 'PlayStation 5 Controller', 1, TRUE),
-- Xbox Controller (assuming product_id 22)
(22, 'https://via.placeholder.com/400x400/00bcf2/ffffff?text=Xbox+Controller', 'Xbox Wireless Controller', 1, TRUE),
-- Anker PowerCore (assuming product_id 23)
(23, 'https://via.placeholder.com/400x400/0066cc/ffffff?text=Anker+10000', 'Anker PowerCore 10000', 1, TRUE),
-- Belkin Charger (assuming product_id 24)
(24, 'https://via.placeholder.com/400x400/000000/ffffff?text=Belkin+3in1', 'Belkin 3-in-1 Wireless Charger', 1, TRUE);

-- Add product attributes for better search and filtering
INSERT IGNORE INTO product_attributes (product_id, attribute_name, attribute_value) VALUES
-- Samsung Galaxy A54 5G
(7, 'Color', 'Awesome Graphite'),
(7, 'Storage', '128GB'),
(7, 'RAM', '8GB'),
(7, 'Screen Size', '6.4 inches'),
(7, 'Network', '5G'),

-- iPhone 13
(8, 'Color', 'Blue'),
(8, 'Storage', '128GB'),
(8, 'Screen Size', '6.1 inches'),
(8, 'Network', '5G'),

-- Redmi Note 12 Pro
(9, 'Color', 'Polar White'),
(9, 'Storage', '256GB'),
(9, 'RAM', '8GB'),
(9, 'Screen Size', '6.67 inches'),
(9, 'Network', '4G'),

-- Dell Inspiron
(10, 'Processor', 'Intel Core i3'),
(10, 'RAM', '4GB'),
(10, 'Storage', '1TB HDD'),
(10, 'Screen Size', '15.6 inches'),
(10, 'OS', 'Windows 11'),

-- MacBook Air M2
(11, 'Processor', 'Apple M2'),
(11, 'RAM', '8GB'),
(11, 'Storage', '256GB SSD'),
(11, 'Screen Size', '13.6 inches'),
(11, 'OS', 'macOS'),

-- Sony Headphones
(13, 'Type', 'Over-ear'),
(13, 'Connectivity', 'Bluetooth 5.2'),
(13, 'Battery Life', '35 hours'),
(13, 'Noise Cancellation', 'Yes'),

-- JBL Speaker
(14, 'Type', 'Portable'),
(14, 'Connectivity', 'Bluetooth 5.1'),
(14, 'Battery Life', '12 hours'),
(14, 'Water Resistance', 'IP67');

COMMIT;
