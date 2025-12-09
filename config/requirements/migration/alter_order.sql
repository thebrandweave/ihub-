ALTER TABLE orders
ADD COLUMN order_number VARCHAR(50) UNIQUE AFTER order_id,
ADD COLUMN address_id INT AFTER user_id,
ADD COLUMN delivered_at DATETIME NULL,
ADD COLUMN cod_verified TINYINT(1) DEFAULT 0;


ALTER TABLE orders
ADD CONSTRAINT fk_orders_address
FOREIGN KEY (address_id) REFERENCES addresses(address_id)
ON DELETE SET NULL;
