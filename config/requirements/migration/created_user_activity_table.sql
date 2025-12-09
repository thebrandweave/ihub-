CREATE TABLE user_activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
