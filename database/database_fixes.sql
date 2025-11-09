-- ===============================================
-- DATABASE STRUCTURE FIXES
-- Ensure all tables have required columns
-- ===============================================

-- Fix any missing columns that might cause issues

-- Add missing columns to tables if they don't exist
-- (Using IF NOT EXISTS equivalent for MySQL)

-- Check and add is_active to addresses if missing (already done but for completeness)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'addresses' 
     AND table_schema = 'auth_db' 
     AND column_name = 'is_active') > 0,
    'SELECT "is_active column already exists in addresses";',
    'ALTER TABLE addresses ADD COLUMN is_active BOOLEAN DEFAULT TRUE;'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ensure products table has status column (some queries might use it)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'products' 
     AND table_schema = 'auth_db' 
     AND column_name = 'status') > 0,
    'SELECT "status column already exists in products";',
    'ALTER TABLE products ADD COLUMN status ENUM(''active'', ''inactive'', ''discontinued'') DEFAULT ''active'';'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ensure orders have all necessary columns
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'orders' 
     AND table_schema = 'auth_db' 
     AND column_name = 'customer_id') > 0,
    'SELECT "customer_id column already exists in orders";',
    'ALTER TABLE orders ADD COLUMN customer_id INT AFTER user_id;'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update customer_id to match user_id where null
UPDATE orders SET customer_id = user_id WHERE customer_id IS NULL;

-- Make sure we have featured column index for better performance
CREATE INDEX IF NOT EXISTS idx_products_featured ON products(is_featured);
CREATE INDEX IF NOT EXISTS idx_products_active ON products(is_active);
CREATE INDEX IF NOT EXISTS idx_addresses_active ON addresses(is_active);

COMMIT;
