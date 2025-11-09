-- ===============================================
-- KENYAN MARKET SPECIFIC PRODUCTS
-- Popular items commonly found in Kenyan stores
-- ===============================================

-- Insert more Kenyan-market focused products
INSERT IGNORE INTO products (name, description, short_description, sku, category_id, brand_id, price, compare_price, stock_quantity, is_featured) VALUES

-- More Mobile Phones (Popular in Kenya)
('Oppo A78 5G', 'Mid-range smartphone with 50MP camera, 6.56" display, 5000mAh battery, 8GB RAM, 256GB storage. Great value for money with 5G connectivity.', 'Affordable 5G smartphone with excellent camera and long battery', 'OPP-A78-5G', 3, 22, 35000.00, 40000.00, 25, TRUE),

('Huawei Y90', 'Large screen smartphone with 6.7" display, 5000mAh battery, 48MP triple camera, 128GB storage. Perfect for entertainment and productivity.', 'Large screen smartphone perfect for entertainment', 'HUA-Y90-128', 3, 23, 28000.00, 32000.00, 30, FALSE),

('Nokia G60 5G', 'Durable smartphone with 6.58" display, 4500mAh battery, 50MP triple camera, Android One. Built to last with regular security updates.', 'Durable 5G smartphone with Android One', 'NOK-G60-5G', 3, 24, 38000.00, 43000.00, 20, FALSE),

-- Tablets (Growing market in Kenya)
('Samsung Galaxy Tab A8', '10.5" Android tablet with 4GB RAM, 64GB storage, 7040mAh battery. Perfect for work, study, and entertainment.', 'Affordable Android tablet for work and entertainment', 'SAM-TAB-A8', 2, 5, 32000.00, 38000.00, 15, TRUE),

('Huawei MatePad T10s', '10.1" tablet with 3GB RAM, 64GB storage, 5100mAh battery, Kids Corner feature. Family-friendly tablet at great value.', 'Family-friendly tablet with Kids Corner feature', 'HUA-MPAD-T10S', 2, 23, 25000.00, 30000.00, 20, FALSE),

-- Power Solutions (Very important in Kenya)
('Oraimo PowerBox 400', 'Portable power station with 400Wh capacity, multiple outlets, solar charging capability. Perfect for load shedding and outdoor use.', 'Portable power station perfect for load shedding', 'ORA-PB400', 1, 4, 45000.00, 52000.00, 10, TRUE),

('Anker PowerCore 26800', 'High-capacity power bank with 26800mAh, fast charging, multiple ports. Keep all your devices charged during power outages.', 'High-capacity power bank for multiple devices', 'ANK-PC26K', 1, 14, 8500.00, 10500.00, 35, TRUE),

('Goal Zero Torch 500', 'Multi-purpose flashlight with power bank, solar panel, hand crank, radio. Essential for emergencies and outdoor activities.', 'Multi-purpose emergency flashlight with power bank', 'GZ-TORCH500', 1, 25, 12000.00, 15000.00, 25, FALSE),

-- Solar Solutions (Growing in Kenya)
('Victron Solar Panel 100W', 'Monocrystalline solar panel with 100W output, 25-year warranty, weather-resistant. Perfect for home solar systems.', 'High-efficiency 100W solar panel for home use', 'VIC-SP100W', 1, 26, 18000.00, 22000.00, 15, FALSE),

('MPPT Solar Charge Controller', '30A MPPT solar charge controller with LCD display, multiple protection features. Optimize your solar power system.', 'Smart solar charge controller with LCD display', 'MPPT-30A-LCD', 1, 27, 8500.00, 11000.00, 20, FALSE),

-- Kitchen Appliances (Popular in Kenyan homes)
('Ramtons Microwave 20L', '20-liter microwave oven with digital display, 8 preset menus, child lock. Perfect for modern Kenyan kitchens.', 'Digital microwave oven perfect for modern kitchens', 'RAM-MW20L', 5, 28, 15500.00, 18000.00, 18, FALSE),

('Sayona Rice Cooker 1.8L', 'Electric rice cooker with 1.8L capacity, keep warm function, non-stick inner pot. Cook perfect rice every time.', 'Electric rice cooker with keep warm function', 'SAY-RC18L', 5, 29, 6500.00, 8000.00, 25, FALSE),

('Binatone Blender BLG-555', 'High-speed blender with 1.5L glass jar, multiple speed settings, ice crushing function. Perfect for smoothies and food prep.', 'High-speed blender perfect for smoothies', 'BIN-BLG555', 5, 30, 8500.00, 10500.00, 20, FALSE),

-- Home Security (Growing concern)
('Hikvision IP Camera', 'Wi-Fi security camera with night vision, motion detection, mobile app control. Keep your home secure 24/7.', 'Wi-Fi security camera with night vision and mobile app', 'HIK-IPCAM-WIFI', 1, 31, 12500.00, 15000.00, 30, FALSE),

('Yale Digital Door Lock', 'Keyless entry door lock with fingerprint, PIN, and key access. Modern security for your home or office.', 'Keyless entry door lock with multiple access methods', 'YALE-DDL-FP', 5, 32, 35000.00, 42000.00, 12, TRUE),

-- Networking (Essential for remote work)
('Huawei 4G Router B315', '4G LTE router with Wi-Fi hotspot, supports up to 32 devices, external antenna ports. Perfect for areas with poor fixed internet.', '4G LTE router perfect for areas with poor internet', 'HUA-4G-B315', 5, 23, 15000.00, 18000.00, 25, TRUE),

('D-Link AC1200 Router', 'Dual-band Wi-Fi router with gigabit ports, guest network, parental controls. Fast and reliable internet for homes.', 'Dual-band Wi-Fi router with advanced features', 'DLK-AC1200', 5, 33, 9500.00, 12000.00, 20, FALSE);

-- Insert additional brands for new products
INSERT IGNORE INTO brands (name, description, logo_url) VALUES
('Oppo', 'Chinese smartphone manufacturer known for camera technology', 'https://via.placeholder.com/150x50/1ba784/ffffff?text=Oppo'),
('Huawei', 'Chinese technology company specializing in telecommunications', 'https://via.placeholder.com/150x50/ff0000/ffffff?text=Huawei'),
('Nokia', 'Finnish telecommunications company with durable phones', 'https://via.placeholder.com/150x50/124191/ffffff?text=Nokia'),
('Goal Zero', 'American company specializing in portable solar power', 'https://via.placeholder.com/150x50/f4a000/ffffff?text=Goal+Zero'),
('Victron Energy', 'Dutch company specializing in solar power solutions', 'https://via.placeholder.com/150x50/4472c4/ffffff?text=Victron'),
('MPPT Tech', 'Solar charge controller manufacturer', 'https://via.placeholder.com/150x50/2e7d32/ffffff?text=MPPT'),
('Ramtons', 'Kenyan home appliance brand', 'https://via.placeholder.com/150x50/d32f2f/ffffff?text=Ramtons'),
('Sayona', 'Popular home appliance brand in East Africa', 'https://via.placeholder.com/150x50/7b1fa2/ffffff?text=Sayona'),
('Binatone', 'Home appliance manufacturer', 'https://via.placeholder.com/150x50/f57c00/ffffff?text=Binatone'),
('Hikvision', 'Chinese security camera manufacturer', 'https://via.placeholder.com/150x50/1976d2/ffffff?text=Hikvision'),
('Yale', 'American lock manufacturer', 'https://via.placeholder.com/150x50/424242/ffffff?text=Yale'),
('D-Link', 'Taiwanese networking equipment company', 'https://via.placeholder.com/150x50/2e7d32/ffffff?text=D-Link');

-- Add images for new products (starting from product_id 38)
INSERT IGNORE INTO product_images (product_id, image_url, alt_text, sort_order, is_primary) VALUES
(38, 'https://via.placeholder.com/400x400/1ba784/ffffff?text=Oppo+A78', 'Oppo A78 5G', 1, TRUE),
(39, 'https://via.placeholder.com/400x400/ff0000/ffffff?text=Huawei+Y90', 'Huawei Y90', 1, TRUE),
(40, 'https://via.placeholder.com/400x400/124191/ffffff?text=Nokia+G60', 'Nokia G60 5G', 1, TRUE),
(41, 'https://via.placeholder.com/400x400/1f4788/ffffff?text=Galaxy+Tab+A8', 'Samsung Galaxy Tab A8', 1, TRUE),
(42, 'https://via.placeholder.com/400x400/ff0000/ffffff?text=MatePad+T10s', 'Huawei MatePad T10s', 1, TRUE),
(43, 'https://via.placeholder.com/400x400/ff0099/ffffff?text=PowerBox+400', 'Oraimo PowerBox 400', 1, TRUE),
(44, 'https://via.placeholder.com/400x400/0066cc/ffffff?text=PowerCore+26K', 'Anker PowerCore 26800', 1, TRUE),
(45, 'https://via.placeholder.com/400x400/f4a000/ffffff?text=Torch+500', 'Goal Zero Torch 500', 1, TRUE),
(46, 'https://via.placeholder.com/400x400/4472c4/ffffff?text=Solar+Panel', 'Victron Solar Panel 100W', 1, TRUE),
(47, 'https://via.placeholder.com/400x400/2e7d32/ffffff?text=MPPT+Controller', 'MPPT Solar Charge Controller', 1, TRUE),
(48, 'https://via.placeholder.com/400x400/d32f2f/ffffff?text=Microwave', 'Ramtons Microwave 20L', 1, TRUE),
(49, 'https://via.placeholder.com/400x400/7b1fa2/ffffff?text=Rice+Cooker', 'Sayona Rice Cooker', 1, TRUE),
(50, 'https://via.placeholder.com/400x400/f57c00/ffffff?text=Blender', 'Binatone Blender', 1, TRUE),
(51, 'https://via.placeholder.com/400x400/1976d2/ffffff?text=IP+Camera', 'Hikvision IP Camera', 1, TRUE),
(52, 'https://via.placeholder.com/400x400/424242/ffffff?text=Digital+Lock', 'Yale Digital Door Lock', 1, TRUE),
(53, 'https://via.placeholder.com/400x400/ff0000/ffffff?text=4G+Router', 'Huawei 4G Router B315', 1, TRUE),
(54, 'https://via.placeholder.com/400x400/2e7d32/ffffff?text=D-Link+Router', 'D-Link AC1200 Router', 1, TRUE);

COMMIT;
