-- ==========================================
-- SAFE MIGRATION: Update to Product Attributes Schema
-- ==========================================
-- This version handles errors gracefully
-- Run this on your existing database

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

-- Step 2: Add attribute_id column (ignore error if exists)
SET @sql = CONCAT('ALTER TABLE products ADD COLUMN attribute_id INT AFTER description');
SET @ignore = (
    SELECT IF(
        (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
         WHERE TABLE_SCHEMA = DATABASE() 
         AND TABLE_NAME = 'products' 
         AND COLUMN_NAME = 'attribute_id') > 0,
        'SELECT "Column already exists"',
        @sql
    )
);
PREPARE stmt FROM @ignore;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 3: Remove brand column (ignore error if doesn't exist)
SET @sql = CONCAT('ALTER TABLE products DROP COLUMN brand');
SET @ignore = (
    SELECT IF(
        (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
         WHERE TABLE_SCHEMA = DATABASE() 
         AND TABLE_NAME = 'products' 
         AND COLUMN_NAME = 'brand') = 0,
        'SELECT "Column does not exist"',
        @sql
    )
);
PREPARE stmt FROM @ignore;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 4: Remove variant_id from cart (ignore error if doesn't exist)
SET @sql = CONCAT('ALTER TABLE cart DROP COLUMN variant_id');
SET @ignore = (
    SELECT IF(
        (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
         WHERE TABLE_SCHEMA = DATABASE() 
         AND TABLE_NAME = 'cart' 
         AND COLUMN_NAME = 'variant_id') = 0,
        'SELECT "Column does not exist"',
        @sql
    )
);
PREPARE stmt FROM @ignore;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 5: Remove variant_id from order_items (ignore error if doesn't exist)
SET @sql = CONCAT('ALTER TABLE order_items DROP COLUMN variant_id');
SET @ignore = (
    SELECT IF(
        (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
         WHERE TABLE_SCHEMA = DATABASE() 
         AND TABLE_NAME = 'order_items' 
         AND COLUMN_NAME = 'variant_id') = 0,
        'SELECT "Column does not exist"',
        @sql
    )
);
PREPARE stmt FROM @ignore;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 6: Drop old tables (safe - won't error if they don't exist)
DROP TABLE IF EXISTS product_variant_attributes;
DROP TABLE IF EXISTS product_variants;
DROP TABLE IF EXISTS attribute_values;
DROP TABLE IF EXISTS product_groups;

-- ==========================================
-- MIGRATION COMPLETE
-- ==========================================
SELECT 'Migration completed successfully!' AS status;






