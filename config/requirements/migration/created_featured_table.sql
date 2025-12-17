-- ==========================================
-- 1️⃣8️⃣ FEATURED PRODUCTS TABLE (Simple)
-- ==========================================
CREATE TABLE featured_products (
    product_id INT PRIMARY KEY,
    added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
);