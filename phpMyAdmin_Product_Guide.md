# phpMyAdmin Product Management Guide

## Accessing phpMyAdmin

1. **Open your web browser** and navigate to:
   ```
   http://localhost/phpmyadmin/
   ```

2. **Login credentials:**
   - Username: `root`
   - Password: `devyan2005`
   - Server: `localhost` (leave blank if auto-filled)

## Database Structure

- **Database Name:** `auth_db`
- **Main Tables:**
  - `products` - Main product catalog
  - `brands` - Product brands/manufacturers
  - `categories` - Product categories
  - `product_images` - Product images
  - `product_attributes` - Product specifications

## Adding Products via phpMyAdmin

### Method 1: Using SQL Tab

1. Click on the `auth_db` database
2. Click on the **SQL** tab
3. Paste the following template and modify values:

```sql
-- Add a new product
INSERT INTO products (name, description, short_description, sku, category_id, brand_id, price, compare_price, stock_quantity, is_featured) 
VALUES (
    'Product Name Here',
    'Detailed product description with features and benefits',
    'Short product description for listings',
    'UNIQUE-SKU-001',
    3,  -- Category ID (1=Electronics, 2=Computers, 3=Mobile Phones, 4=Audio, 5=Home & Office)
    1,  -- Brand ID (check brands table for IDs)
    25000.00,  -- Current price
    30000.00,  -- Compare price (original/crossed-out price)
    50,  -- Stock quantity
    TRUE  -- Is featured (TRUE/FALSE)
);

-- Add product image (replace 'X' with the actual product ID from above)
INSERT INTO product_images (product_id, image_url, alt_text, sort_order, is_primary) 
VALUES (
    X,  -- Product ID from the product you just inserted
    'https://via.placeholder.com/400x400/007bff/ffffff?text=Your+Product',
    'Product Alt Text',
    1,
    TRUE
);

-- Add product attributes (replace 'X' with actual product ID)
INSERT INTO product_attributes (product_id, attribute_name, attribute_value) 
VALUES 
(X, 'Color', 'Black'),
(X, 'Size', 'Medium'),
(X, 'Warranty', '1 Year');
```

### Method 2: Using Insert Tab

1. Navigate to `auth_db` > `products` table
2. Click **Insert** tab
3. Fill in the form fields:
   - **name**: Product name
   - **description**: Detailed description
   - **short_description**: Brief description
   - **sku**: Unique product code (e.g., PROD-001)
   - **category_id**: Select from categories table
   - **brand_id**: Select from brands table
   - **price**: Current selling price
   - **compare_price**: Original/crossed-out price (optional)
   - **stock_quantity**: Available stock
   - **is_featured**: 1 for featured, 0 for normal
   - **is_active**: 1 for active, 0 for inactive

## Current Categories (category_id)

| ID | Name | Description |
|----|------|-------------|
| 1 | Electronics | Mobile phones, laptops, and electronic devices |
| 2 | Computers | Laptops, desktops, and computer accessories |
| 3 | Mobile Phones | Smartphones and mobile accessories |
| 4 | Audio | Headphones, speakers, and audio equipment |
| 5 | Home & Office | Printers, office supplies, and home electronics |

## Current Brands (brand_id)

| ID | Brand | Description |
|----|-------|-------------|
| 1 | Safaricom | Leading telecommunications company in Kenya |
| 2 | Tecno | Popular smartphone and laptop brand |
| 3 | Infinix | Affordable smartphones with great features |
| 4 | Oraimo | Audio and mobile accessories brand |
| 5 | Samsung | Global electronics manufacturer |
| 6 | HP | Computer and printer manufacturer |
| 7 | Apple | American multinational technology company |
| 8 | Xiaomi | Chinese electronics company known for smartphones |
| ... | (and more) | Check the brands table for complete list |

## Product Management Tips

### Adding Featured Products
- Set `is_featured = 1` to display on homepage
- Featured products appear in the dashboard and main pages

### Setting Stock Levels
- `stock_quantity`: Current available stock
- `min_stock_level`: Minimum stock before low stock alert (default: 5)

### Product Images
- Always add at least one image with `is_primary = TRUE`
- Use placeholder images: `https://via.placeholder.com/400x400/COLOR/ffffff?text=Your+Text`
- Real images should be 400x400 pixels or similar square format

### Product Attributes
- Add specifications like Color, Size, Warranty, etc.
- These help with filtering and search functionality

## Quick Commands

### View all products:
```sql
SELECT id, name, price, stock_quantity, is_featured 
FROM products 
ORDER BY created_at DESC;
```

### Update stock quantity:
```sql
UPDATE products 
SET stock_quantity = 100 
WHERE id = 1;
```

### Mark product as featured:
```sql
UPDATE products 
SET is_featured = TRUE 
WHERE id = 1;
```

### Deactivate a product:
```sql
UPDATE products 
SET is_active = FALSE 
WHERE id = 1;
```

## Current Product Count
- Total Products: **37**
- Featured Products: Check with `SELECT COUNT(*) FROM products WHERE is_featured = TRUE;`
- Active Products: Check with `SELECT COUNT(*) FROM products WHERE is_active = TRUE;`
