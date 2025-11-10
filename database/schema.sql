-- ========================================
-- 3D Print Pro Database Schema
-- ========================================

-- Create orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    type ENUM('order', 'contact') DEFAULT 'contact',
    
    -- Client information
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(20) NOT NULL,
    telegram VARCHAR(100),
    
    -- Order details
    service VARCHAR(255),
    subject VARCHAR(255),
    message TEXT,
    amount DECIMAL(10, 2) DEFAULT 0,
    
    -- Calculator data (JSON)
    calculator_data JSON,
    
    -- Status tracking
    status ENUM('new', 'processing', 'completed', 'cancelled') DEFAULT 'new',
    telegram_sent BOOLEAN DEFAULT FALSE,
    telegram_error TEXT,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for performance
    INDEX idx_order_number (order_number),
    INDEX idx_phone (phone),
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create settings table (for storing configuration)
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default Telegram Chat ID setting (empty by default)
INSERT INTO settings (setting_key, setting_value) 
VALUES ('telegram_chat_id', '') 
ON DUPLICATE KEY UPDATE setting_key=setting_key;
