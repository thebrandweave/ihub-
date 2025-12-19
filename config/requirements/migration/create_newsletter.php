-- ==========================================
-- 19️⃣ NEWSLETTER SUBSCRIBERS
-- ==========================================
CREATE TABLE newsletter_subscribers (
    subscriber_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL UNIQUE,
    user_id INT NULL, -- Optional: links to users table if they are registered
    status ENUM('subscribed', 'unsubscribed') DEFAULT 'subscribed',
    token VARCHAR(64) NULL, -- For secure one-click unsubscribe links
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_newsletter_email (email)
);