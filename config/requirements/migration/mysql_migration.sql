-- ==========================================
-- MIGRATION: Update to Product Attributes Schema
-- ==========================================
-- Run this on your existing database
-- Compatible with MySQL/MariaDB

USE ihub_electronics;

-- Step 1: Create product_attributes table
CREATE TABLE IF NOT EXISTS product_attributes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    attribute VARCHAR(100) NOT NULL COMMENT 'Attribute name (e.g., Color, Storage, Memory)',
    value VARCHAR(100) NOT NULL COMMENT 'Attribute value (e.g., Black, 256GB, 8GB)',
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id)
);

-- Step 2: Add attribute_id column to products
-- (Run this even if column exists - it will show an error but won't break)
ALTER TABLE products ADD COLUMN attribute_id INT AFTER description;

-- Step 3: Remove brand column from products
-- (Run this even if column doesn't exist - it will show an error but won't break)
ALTER TABLE products DROP COLUMN brand;

-- Step 4: Remove variant_id from cart table
ALTER TABLE cart DROP COLUMN variant_id;

-- Step 5: Remove variant_id from order_items table  
ALTER TABLE order_items DROP COLUMN variant_id;

-- Step 6: Drop old variant/attribute tables (if they exist)
DROP TABLE IF EXISTS product_variant_attributes;
DROP TABLE IF EXISTS product_variants;
DROP TABLE IF EXISTS attribute_values;
DROP TABLE IF EXISTS product_groups;

-- ==========================================
-- MIGRATION COMPLETE
-- ==========================================
SELECT 'Migration completed successfully!' AS status;




