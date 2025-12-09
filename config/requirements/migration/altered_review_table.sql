ALTER TABLE reviews
ADD COLUMN status ENUM('pending','approved','rejected') DEFAULT 'pending';
