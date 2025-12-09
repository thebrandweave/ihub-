-- ==========================================
-- MIGRATION: Fix Orders and Addresses
-- Purpose: Add missing addresses table and update orders table
-- Date: 2025-01-XX
-- ==========================================

-- Create addresses table if it doesn't exist
CREATE TABLE IF NOT EXISTS addresses (
    address_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    full_name VARCHAR(100),
    phone VARCHAR(15),
    address_line1 TEXT NOT NULL,
    address_line2 TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    postal_code VARCHAR(15),
    country VARCHAR(50) DEFAULT 'India',
    is_default TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Add missing columns to orders table if they don't exist
-- Note: MySQL will throw an error if column already exists, so we check first

-- Add order_number column
SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_schema = DATABASE() 
     AND table_name = 'orders' 
     AND column_name = 'order_number') = 0,
    'ALTER TABLE orders ADD COLUMN order_number VARCHAR(50) UNIQUE AFTER order_id',
    'SELECT "order_number column already exists" AS Info'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add address_id column
SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_schema = DATABASE() 
     AND table_name = 'orders' 
     AND column_name = 'address_id') = 0,
    'ALTER TABLE orders ADD COLUMN address_id INT AFTER user_id',
    'SELECT "address_id column already exists" AS Info'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add delivered_at column
SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_schema = DATABASE() 
     AND table_name = 'orders' 
     AND column_name = 'delivered_at') = 0,
    'ALTER TABLE orders ADD COLUMN delivered_at DATETIME NULL',
    'SELECT "delivered_at column already exists" AS Info'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add cod_verified column
SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_schema = DATABASE() 
     AND table_name = 'orders' 
     AND column_name = 'cod_verified') = 0,
    'ALTER TABLE orders ADD COLUMN cod_verified TINYINT(1) DEFAULT 0',
    'SELECT "cod_verified column already exists" AS Info'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add foreign key constraint for address_id (if not exists)
SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
     WHERE table_schema = DATABASE() 
     AND table_name = 'orders' 
     AND constraint_name = 'fk_orders_address') = 0,
    'ALTER TABLE orders ADD CONSTRAINT fk_orders_address FOREIGN KEY (address_id) REFERENCES addresses(address_id) ON DELETE SET NULL',
    'SELECT "fk_orders_address constraint already exists" AS Info'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add status column to reviews table if it doesn't exist
SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_schema = DATABASE() 
     AND table_name = 'reviews' 
     AND column_name = 'status') = 0,
    'ALTER TABLE reviews ADD COLUMN status ENUM(''pending'',''approved'',''rejected'') DEFAULT ''pending'' AFTER comment',
    'SELECT "reviews status column already exists" AS Info'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Create order_status_history table if it doesn't exist
CREATE TABLE IF NOT EXISTS order_status_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled'),
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
);

-- Create user_activity_logs table if it doesn't exist
CREATE TABLE IF NOT EXISTS user_activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Success message
SELECT 'Migration completed successfully! All tables and columns are now up to date.' AS Result;

