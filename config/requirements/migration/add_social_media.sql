-- ==========================================
-- 19️⃣ SOCIAL MEDIA TABLE (Refined for Admin Ease)
-- ==========================================
CREATE TABLE social_media (
    social_id INT AUTO_INCREMENT PRIMARY KEY,
    platform_name VARCHAR(50) NOT NULL,  
    link_url VARCHAR(255) NOT NULL,        -- The actual link
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
