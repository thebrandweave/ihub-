CREATE TABLE IF NOT EXISTS advertisements (
    ad_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL COMMENT 'Internal campaign name',
    ad_type ENUM('hero_banner', 'popup') DEFAULT 'hero_banner',
    image_url VARCHAR(255) NOT NULL,
    target_url VARCHAR(255) NOT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    priority INT DEFAULT 0,
    clicks INT DEFAULT 0,
    views INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active_ads (status, start_date, end_date, ad_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

