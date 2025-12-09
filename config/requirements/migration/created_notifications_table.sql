-- ==========================================
-- NOTIFICATIONS TABLE
-- ==========================================
-- This migration creates a generic notifications table that can be used for
-- both customers (order updates, promotions, security alerts) and admins
-- (inventory alerts, system alerts).
--
-- Business logic (when to insert notifications) should be handled in PHP,
-- not in database triggers, so it stays easy to debug and portable.

CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,

    -- Recipient of the notification (customer or admin)
    user_id INT NOT NULL,

    -- Categorization for filtering in UI
    type ENUM(
        'order_update',      -- e.g. "Your order has shipped"
        'promotion',         -- e.g. "Price drop on iPhone 15"
        'account_security',  -- e.g. "New login detected"
        'inventory_alert',   -- e.g. "Stock low on MacBook Pro"
        'system_alert'       -- e.g. "Database backup failed"
    ) NOT NULL DEFAULT 'order_update',

    -- Basic content
    title   VARCHAR(255) NOT NULL,
    message TEXT         NOT NULL,

    -- Optional visuals (icon / product thumbnail URL)
    image_url  VARCHAR(255) DEFAULT NULL,

    -- Optional link to open when clicking the notification
    target_url VARCHAR(255) DEFAULT NULL,

    -- Read state
    is_read    TINYINT(1) DEFAULT 0,
    created_at DATETIME   DEFAULT CURRENT_TIMESTAMP,
    read_at    DATETIME   NULL,

    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,

    -- Fast retrieval of newest unread notifications per user
    INDEX idx_user_unread (user_id, is_read, created_at)
);


