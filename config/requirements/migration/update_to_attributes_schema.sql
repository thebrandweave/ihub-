-- ==========================================
-- MIGRATION: Update to Product Attributes Schema
-- ==========================================
-- This migration updates the database to use the new product_attributes system
-- Run this script on your existing database

USE ihub_electronics;

-- ==========================================
-- STEP 1: Create product_attributes table
-- ==========================================
CREATE TABLE IF NOT EXISTS product_attributes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    attribute VARCHAR(100) NOT NULL COMMENT 'Attribute name (e.g., Color, Storage, Memory)',
    value VARCHAR(100) NOT NULL COMMENT 'Attribute value (e.g., Black, 256GB, 8GB)',
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id)
);

-- ==========================================
-- STEP 2: Add attribute_id column to products (if not exists)
-- ==========================================
-- Check if column exists, if not add it
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'ihub_electronics' 
    AND TABLE_NAME = 'products' 
    AND COLUMN_NAME = 'attribute_id'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE products ADD COLUMN attribute_id INT AFTER description',
    'SELECT "Column attribute_id already exists" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ==========================================
-- STEP 3: Remove brand column from products (if exists)
-- ==========================================
-- Check if brand column exists, if yes remove it
SET @brand_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'ihub_electronics' 
    AND TABLE_NAME = 'products' 
    AND COLUMN_NAME = 'brand'
);

SET @sql = IF(@brand_exists > 0,
    'ALTER TABLE products DROP COLUMN brand',
    'SELECT "Column brand does not exist" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ==========================================
-- STEP 4: Remove product_groups table (if exists)
-- ==========================================
DROP TABLE IF EXISTS product_groups;

-- ==========================================
-- STEP 5: Remove variant_id from cart table (if exists)
-- ==========================================
SET @variant_cart_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'ihub_electronics' 
    AND TABLE_NAME = 'cart' 
    AND COLUMN_NAME = 'variant_id'
);

SET @sql = IF(@variant_cart_exists > 0,
    'ALTER TABLE cart DROP COLUMN variant_id, DROP FOREIGN KEY cart_ibfk_3',
    'SELECT "Column variant_id does not exist in cart" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ==========================================
-- STEP 6: Remove variant_id from order_items table (if exists)
-- ==========================================
SET @variant_order_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'ihub_electronics' 
    AND TABLE_NAME = 'order_items' 
    AND COLUMN_NAME = 'variant_id'
);

SET @sql = IF(@variant_order_exists > 0,
    'ALTER TABLE order_items DROP COLUMN variant_id, DROP FOREIGN KEY order_items_ibfk_3',
    'SELECT "Column variant_id does not exist in order_items" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ==========================================
-- STEP 7: Drop old attribute tables if they exist
-- ==========================================
DROP TABLE IF EXISTS product_variant_attributes;
DROP TABLE IF EXISTS product_variants;
DROP TABLE IF EXISTS attribute_values;
DROP TABLE IF EXISTS product_attributes_old;

-- ==========================================
-- MIGRATION COMPLETE
-- ==========================================
SELECT 'Migration completed successfully!' AS status;



