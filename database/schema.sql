-- ========================================
-- 3D Print Pro Database Schema
-- Version: 4.0 (RBAC Authentication System)
-- Last Updated: January 2025
-- MySQL Version: 8.0+ (tested with MySQL 8.0)
-- ========================================
--
-- This schema creates 16 tables for the 3D printing service platform:
-- 1. orders - Customer orders and inquiries (legacy + form integration)
-- 2. settings - Application configuration (NO 'active' column)
-- 3. services - Service offerings
-- 4. portfolio - Project showcase
-- 5. testimonials - Customer reviews
-- 6. faq - Frequently asked questions
-- 7. content_blocks - Dynamic page content
-- 8. forms - Dynamic form definitions
-- 9. form_fields - Form field configurations
-- 10. form_submissions - Form submission records
-- 11. form_submission_values - Individual field values (normalized)
-- 12. settings_audit - Settings change audit log
-- 13. admin_users - Admin user accounts with RBAC (NEW)
-- 14. admin_sessions - Persistent session storage (NEW)
-- 15. admin_login_attempts - Login attempt tracking (NEW)
-- 16. admin_action_logs - Admin action audit log (NEW)
--
-- IMPORTANT NOTES:
-- - Tables WITHOUT 'active' column: orders, settings, form_submissions, form_submission_values, 
--   settings_audit, admin_sessions, admin_login_attempts, admin_action_logs
-- - Tables WITH 'active' column: services, portfolio, testimonials, faq, content_blocks, forms, form_fields
-- - admin_users uses 'status' enum instead of 'active' boolean
-- - This file is IDEMPOTENT - safe to run multiple times
-- - For HARD RESET: uncomment the DROP TABLE statements below
-- - JSON columns are fully supported in MySQL 8.0
-- - All tables use utf8mb4 for full Unicode support (emojis, Russian, etc.)
--
-- EXECUTION ORDER:
-- 1. Run this file via: mysql -u USER -p DATABASE < schema.sql
-- 2. Run seed script: Visit /api/init-database.php in browser or curl
-- 3. Verify: Visit /api/test.php or use database/verify-schema.php
--
-- PRODUCTION TARGET:
-- Host: localhost / 3dprint-omsk.ru
-- Database: ch167436_3dprint
-- User: ch167436_3dprint
--
-- ========================================

-- ========================================
-- OPTIONAL: Hard Reset (DANGER!)
-- Uncomment these lines to drop all tables before recreating
-- WARNING: This will DELETE ALL DATA permanently!
-- ========================================
-- DROP TABLE IF EXISTS form_submission_values;
-- DROP TABLE IF EXISTS form_submissions;
-- DROP TABLE IF EXISTS form_fields;
-- DROP TABLE IF EXISTS forms;
-- DROP TABLE IF EXISTS settings_audit;
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
-- UPDATED: Now supports integration with dynamic forms system
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
    
    -- Form integration (NEW)
    form_submission_id INT NULL,
    form_slug VARCHAR(255) NULL,
    
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
    INDEX idx_type (type),
    INDEX idx_form_slug (form_slug),
    INDEX idx_form_submission_id (form_submission_id)
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
-- TABLE: forms
-- Dynamic form definitions
-- HAS 'active' column for visibility control
-- ========================================
CREATE TABLE IF NOT EXISTS forms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    settings JSON COMMENT 'Form-level settings: validation, notifications, etc.',
    notification_email VARCHAR(255) COMMENT 'Email to notify on submission',
    success_message TEXT COMMENT 'Message to show after successful submission',
    redirect_url VARCHAR(500) COMMENT 'URL to redirect after submission',
    sort_order INT DEFAULT 0,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_slug (slug),
    INDEX idx_active (active),
    INDEX idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE: form_fields
-- Field definitions for forms
-- HAS 'active' column for visibility control
-- ========================================
CREATE TABLE IF NOT EXISTS form_fields (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_id INT NOT NULL,
    name VARCHAR(255) NOT NULL COMMENT 'Field name/key',
    label VARCHAR(255) NOT NULL COMMENT 'Display label',
    type ENUM('text', 'email', 'phone', 'textarea', 'number', 'select', 'checkbox', 'radio', 'file', 'hidden') DEFAULT 'text',
    placeholder VARCHAR(255),
    default_value TEXT,
    validation_rules JSON COMMENT 'Validation rules: required, min, max, pattern, etc.',
    options JSON COMMENT 'Options for select, radio, checkbox fields',
    help_text VARCHAR(500),
    sort_order INT DEFAULT 0,
    required BOOLEAN DEFAULT FALSE,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_form_id (form_id),
    INDEX idx_active (active),
    INDEX idx_sort (sort_order),
    UNIQUE KEY unique_form_field (form_id, name),
    
    FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE: form_submissions
-- Form submission records
-- NO 'active' column - all submissions are kept for history
-- ========================================
CREATE TABLE IF NOT EXISTS form_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_id INT NOT NULL,
    form_slug VARCHAR(255) NOT NULL COMMENT 'Denormalized slug for faster queries',
    submitted_data JSON COMMENT 'Complete submitted data as JSON',
    status ENUM('pending', 'processed', 'archived') DEFAULT 'pending',
    ip_address VARCHAR(45) COMMENT 'Submitter IP address',
    user_agent TEXT COMMENT 'Submitter user agent',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When form was submitted',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_form_id (form_id),
    INDEX idx_form_slug (form_slug),
    INDEX idx_status (status),
    INDEX idx_submitted_at (submitted_at),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE: form_submission_values
-- Normalized field values for form submissions
-- NO 'active' column - all values are kept for history
-- Allows efficient querying of individual field values
-- ========================================
CREATE TABLE IF NOT EXISTS form_submission_values (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_submission_id INT NOT NULL,
    form_field_id INT NULL COMMENT 'NULL if field was deleted',
    field_name VARCHAR(255) NOT NULL COMMENT 'Denormalized field name',
    field_value LONGTEXT COMMENT 'Field value as text',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_submission_id (form_submission_id),
    INDEX idx_field_id (form_field_id),
    INDEX idx_field_name (field_name),
    
    FOREIGN KEY (form_submission_id) REFERENCES form_submissions(id) ON DELETE CASCADE,
    FOREIGN KEY (form_field_id) REFERENCES form_fields(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE: settings_audit
-- Audit log for settings changes
-- NO 'active' column - all audit records are kept
-- ========================================
CREATE TABLE IF NOT EXISTS settings_audit (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL,
    old_value TEXT COMMENT 'Previous value',
    new_value TEXT COMMENT 'New value',
    changed_by VARCHAR(255) COMMENT 'Admin username or system',
    ip_address VARCHAR(45) COMMENT 'IP address of change',
    user_agent TEXT COMMENT 'User agent string',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_setting_key (setting_key),
    INDEX idx_changed_by (changed_by),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Add Foreign Key to orders table
-- Link orders to form submissions
-- ========================================
ALTER TABLE orders 
ADD CONSTRAINT fk_orders_form_submission 
FOREIGN KEY (form_submission_id) REFERENCES form_submissions(id) ON DELETE SET NULL;

-- ========================================
-- TABLE: admin_users
-- Admin user accounts with RBAC support
-- NO 'active' column - uses 'status' enum instead
-- ========================================
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL COMMENT 'bcrypt hash',
    role ENUM('super_admin', 'admin', 'editor') DEFAULT 'admin',
    status ENUM('active', 'inactive', 'locked') DEFAULT 'active',
    last_login_at TIMESTAMP NULL,
    last_login_ip VARCHAR(45) NULL,
    failed_login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL COMMENT 'Automatic unlock time after rate limit',
    remember_token VARCHAR(100) NULL COMMENT 'For remember me functionality',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_role (role),
    INDEX idx_remember_token (remember_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE: admin_sessions
-- Persistent admin session storage
-- NO 'active' column - sessions are either valid or deleted
-- ========================================
CREATE TABLE IF NOT EXISTS admin_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(128) NOT NULL UNIQUE COMMENT 'PHP session ID',
    user_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    csrf_token VARCHAR(64) NULL COMMENT 'Session-bound CSRF token',
    expires_at TIMESTAMP NOT NULL COMMENT 'Session expiration time',
    last_activity_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_session_id (session_id),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at),
    INDEX idx_last_activity_at (last_activity_at),
    
    FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE: admin_login_attempts
-- Login attempt tracking for rate limiting
-- NO 'active' column - all attempts are logged permanently
-- ========================================
CREATE TABLE IF NOT EXISTS admin_login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    success BOOLEAN NOT NULL DEFAULT FALSE,
    failure_reason VARCHAR(255) NULL COMMENT 'Why login failed: invalid_credentials, locked_account, etc.',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_ip_address (ip_address),
    INDEX idx_success (success),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE: admin_action_logs
-- Audit log for admin actions
-- NO 'active' column - all actions are logged permanently
-- ========================================
CREATE TABLE IF NOT EXISTS admin_action_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL COMMENT 'Action type: create, update, delete, etc.',
    entity_type VARCHAR(100) NULL COMMENT 'Entity being acted upon: service, order, settings, etc.',
    entity_id INT NULL COMMENT 'ID of the entity',
    payload JSON COMMENT 'Additional action details and context',
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_entity_type (entity_type),
    INDEX idx_entity_id (entity_id),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Schema Creation Complete
-- ========================================
-- Next Steps:
-- 1. Verify tables: SHOW TABLES;
-- 2. Run seed script: Visit /api/init-database.php
-- 3. Create first admin: php scripts/create-admin.php
-- 4. Verify data: SELECT COUNT(*) FROM [table_name];
-- ========================================
