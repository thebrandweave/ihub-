-- ==========================================
-- MIGRATION: Add Brands Table
-- ==========================================
-- Run this on your existing database

USE ihub_electronics;

-- Step 1: Create brands table
CREATE TABLE IF NOT EXISTS brands (
    brand_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    logo VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Step 2: Add brand_id column to products table
ALTER TABLE products ADD COLUMN brand_id INT AFTER category_id;

-- Step 3: Add foreign key constraint
ALTER TABLE products ADD CONSTRAINT fk_products_brand 
    FOREIGN KEY (brand_id) REFERENCES brands(brand_id) ON DELETE SET NULL;

-- Step 4: Add index for better performance
CREATE INDEX idx_products_brand ON products(brand_id);

-- ==========================================
-- MIGRATION COMPLETE
-- ==========================================
SELECT 'Brands table migration completed successfully!' AS status;



