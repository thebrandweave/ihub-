-- ==========================================
-- SIMPLE MIGRATION: Update to Product Attributes Schema
-- ==========================================
-- Run this on your existing database
-- WARNING: This will remove brand column and variant tables

USE ihub_electronics;

-- Step 1: Create product_attributes table
CREATE TABLE IF NOT EXISTS product_attributes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    attribute VARCHAR(100) NOT NULL,
    value VARCHAR(100) NOT NULL,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id)
);

-- Step 2: Add attribute_id to products (if needed)
ALTER TABLE products ADD COLUMN IF NOT EXISTS attribute_id INT AFTER description;

-- Step 3: Remove brand column (if exists)
ALTER TABLE products DROP COLUMN IF EXISTS brand;

-- Step 4: Remove variant_id from cart (if exists)
ALTER TABLE cart DROP COLUMN IF EXISTS variant_id;

-- Step 5: Remove variant_id from order_items (if exists)
ALTER TABLE order_items DROP COLUMN IF EXISTS variant_id;

-- Step 6: Drop old tables (if they exist)
DROP TABLE IF EXISTS product_variant_attributes;
DROP TABLE IF EXISTS product_variants;
DROP TABLE IF EXISTS attribute_values;
DROP TABLE IF EXISTS product_attributes_old;
DROP TABLE IF EXISTS product_groups;

SELECT 'Migration completed!' AS status;

