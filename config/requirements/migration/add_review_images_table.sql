-- Migration: Add review_images table and keep legacy image column
-- Run this after add_review_image_column.sql if you already added the single image column.

CREATE TABLE IF NOT EXISTS review_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    review_id INT NOT NULL,
    image VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (review_id) REFERENCES reviews(review_id) ON DELETE CASCADE,
    INDEX idx_review_id (review_id)
);



