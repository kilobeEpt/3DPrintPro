-- ========================================
-- 3D Print Pro Database Schema
-- Version: 2.0 (Complete)
-- Last Updated: January 2025
-- ========================================
--
-- This schema creates 7 tables for the 3D printing service platform:
-- 1. orders - Customer orders and inquiries
-- 2. settings - Application configuration (NO 'active' column)
-- 3. services - Service offerings
-- 4. portfolio - Project showcase
-- 5. testimonials - Customer reviews
-- 6. faq - Frequently asked questions
-- 7. content_blocks - Dynamic page content
--
-- IMPORTANT NOTES:
-- - Tables WITHOUT 'active' column: orders, settings
-- - Tables WITH 'active' column: services, portfolio, testimonials, faq, content_blocks
-- - This file is IDEMPOTENT - safe to run multiple times
-- - For HARD RESET: uncomment the DROP TABLE statements below
--
-- ========================================

-- ========================================
-- OPTIONAL: Hard Reset (DANGER!)
-- Uncomment these lines to drop all tables before recreating
-- WARNING: This will DELETE ALL DATA permanently!
-- ========================================
-- DROP TABLE IF EXISTS orders;
-- DROP TABLE IF EXISTS settings;
-- DROP TABLE IF EXISTS services;
-- DROP TABLE IF EXISTS portfolio;
-- DROP TABLE IF EXISTS testimonials;
-- DROP TABLE IF EXISTS faq;
-- DROP TABLE IF EXISTS content_blocks;

-- ========================================
-- TABLE: orders
-- Stores customer orders and contact form submissions
-- NO 'active' column - all orders are kept for history
-- ========================================
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
    INDEX idx_created_at (created_at),
    INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE: settings
-- Application configuration and settings
-- NO 'active' column - all settings are always active
-- ========================================
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default Telegram Chat ID setting (idempotent)
INSERT INTO settings (setting_key, setting_value) 
VALUES ('telegram_chat_id', '') 
ON DUPLICATE KEY UPDATE setting_key=setting_key;

-- ========================================
-- TABLE: services
-- Service offerings and pricing
-- HAS 'active' column for visibility control
-- ========================================
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    icon VARCHAR(255),
    description TEXT,
    features JSON,
    price VARCHAR(100),
    category VARCHAR(100),
    sort_order INT DEFAULT 0,
    active BOOLEAN DEFAULT TRUE,
    featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_active (active),
    INDEX idx_featured (featured),
    INDEX idx_sort (sort_order),
    INDEX idx_slug (slug),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE: portfolio
-- Project showcase and case studies
-- HAS 'active' column for visibility control
-- ========================================
CREATE TABLE IF NOT EXISTS portfolio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    image_url VARCHAR(500),
    category VARCHAR(100),
    tags JSON,
    sort_order INT DEFAULT 0,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_active (active),
    INDEX idx_category (category),
    INDEX idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE: testimonials
-- Customer reviews and ratings
-- HAS 'active' column and 'approved' for moderation
-- ========================================
CREATE TABLE IF NOT EXISTS testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    position VARCHAR(255),
    avatar VARCHAR(500),
    text TEXT NOT NULL,
    rating INT DEFAULT 5,
    sort_order INT DEFAULT 0,
    approved BOOLEAN DEFAULT TRUE,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_active (active),
    INDEX idx_approved (approved),
    INDEX idx_rating (rating),
    INDEX idx_sort (sort_order),
    
    CHECK (rating >= 1 AND rating <= 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE: faq
-- Frequently asked questions
-- HAS 'active' column for visibility control
-- ========================================
CREATE TABLE IF NOT EXISTS faq (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question VARCHAR(500) NOT NULL,
    answer TEXT NOT NULL,
    sort_order INT DEFAULT 0,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_active (active),
    INDEX idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE: content_blocks
-- Dynamic content blocks for pages
-- HAS 'active' column for visibility control
-- ========================================
CREATE TABLE IF NOT EXISTS content_blocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    block_name VARCHAR(255) NOT NULL UNIQUE,
    title VARCHAR(500),
    content LONGTEXT,
    data JSON,
    page VARCHAR(100),
    sort_order INT DEFAULT 0,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_block_name (block_name),
    INDEX idx_page (page),
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Schema Creation Complete
-- ========================================
-- Next Steps:
-- 1. Verify tables: SHOW TABLES;
-- 2. Run seed script: Visit /api/init-database.php
-- 3. Verify data: SELECT COUNT(*) FROM [table_name];
-- ========================================
