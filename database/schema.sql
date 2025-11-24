-- ========================================
-- 3D Print Pro Database Schema
-- Version: 5.0 (Orders Domain with History & Notes) - CORRECTED
-- Last Updated: January 2025
-- MySQL Version: 8.0+ (tested with MySQL 8.0)
-- ========================================
--
-- This schema creates 18 tables for the 3D printing service platform:
-- 1. admin_users - Admin user accounts with RBAC (NO DEPENDENCIES)
-- 2. settings - Application configuration (NO DEPENDENCIES)
-- 3. services - Service offerings (NO DEPENDENCIES)
-- 4. portfolio - Project showcase (NO DEPENDENCIES)
-- 5. testimonials - Customer reviews (NO DEPENDENCIES)
-- 6. faq - Frequently asked questions (NO DEPENDENCIES)
-- 7. content_blocks - Dynamic page content (NO DEPENDENCIES)
-- 8. forms - Dynamic form definitions (NO DEPENDENCIES)
-- 9. settings_audit - Settings change audit log (NO DEPENDENCIES)
-- 10. admin_login_attempts - Login attempt tracking (NO DEPENDENCIES)
-- 11. form_fields - Form field configurations (depends on: forms)
-- 12. form_submissions - Form submission records (depends on: forms)
-- 13. admin_sessions - Persistent session storage (depends on: admin_users)
-- 14. admin_action_logs - Admin action audit log (depends on: admin_users)
-- 15. form_submission_values - Individual field values (depends on: form_submissions, form_fields)
-- 16. orders - Customer orders and inquiries (depends on: form_submissions)
-- 17. order_status_history - Order status change tracking (depends on: orders, admin_users)
-- 18. order_notes - Internal order notes (depends on: orders, admin_users)
--
-- IMPORTANT NOTES:
-- - Tables WITHOUT 'active' column: orders, order_status_history, order_notes, settings, 
--   form_submissions, form_submission_values, settings_audit, admin_sessions, 
--   admin_login_attempts, admin_action_logs
-- - Tables WITH 'active' column: services, portfolio, testimonials, faq, content_blocks, forms, form_fields
-- - admin_users uses 'status' enum instead of 'active' boolean
-- - This file is IDEMPOTENT - safe to run multiple times
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
-- Tables are dropped in REVERSE order of dependencies
-- ========================================
-- DROP TABLE IF EXISTS order_notes;
-- DROP TABLE IF EXISTS order_status_history;
-- DROP TABLE IF EXISTS form_submission_values;
-- DROP TABLE IF EXISTS orders;
-- DROP TABLE IF EXISTS form_submissions;
-- DROP TABLE IF EXISTS form_fields;
-- DROP TABLE IF EXISTS admin_action_logs;
-- DROP TABLE IF EXISTS admin_sessions;
-- DROP TABLE IF EXISTS admin_login_attempts;
-- DROP TABLE IF EXISTS settings_audit;
-- DROP TABLE IF EXISTS forms;
-- DROP TABLE IF EXISTS content_blocks;
-- DROP TABLE IF EXISTS faq;
-- DROP TABLE IF EXISTS testimonials;
-- DROP TABLE IF EXISTS portfolio;
-- DROP TABLE IF EXISTS services;
-- DROP TABLE IF EXISTS settings;
-- DROP TABLE IF EXISTS admin_users;

-- ========================================
-- LEVEL 0 TABLES (No Dependencies)
-- These tables must be created FIRST
-- ========================================

-- ========================================
-- TABLE 1: admin_users
-- Admin user accounts with RBAC support
-- NO 'active' column - uses 'status' enum instead
-- DEPENDENCIES: None
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
-- TABLE 2: settings
-- Application configuration and settings
-- NO 'active' column - all settings are always active
-- DEPENDENCIES: None
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
-- TABLE 3: services
-- Service offerings and pricing
-- HAS 'active' column for visibility control
-- DEPENDENCIES: None
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
-- TABLE 4: portfolio
-- Project showcase and case studies
-- HAS 'active' column for visibility control
-- DEPENDENCIES: None
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
-- TABLE 5: testimonials
-- Customer reviews and ratings
-- HAS 'active' column and 'approved' for moderation
-- DEPENDENCIES: None
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
-- TABLE 6: faq
-- Frequently asked questions
-- HAS 'active' column for visibility control
-- DEPENDENCIES: None
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
-- TABLE 7: content_blocks
-- Dynamic content blocks for pages
-- HAS 'active' column for visibility control
-- DEPENDENCIES: None
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
-- TABLE 8: forms
-- Dynamic form definitions
-- HAS 'active' column for visibility control
-- DEPENDENCIES: None
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
-- TABLE 9: settings_audit
-- Audit log for settings changes
-- NO 'active' column - all audit records are kept
-- DEPENDENCIES: None
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
-- TABLE 10: admin_login_attempts
-- Login attempt tracking for rate limiting
-- NO 'active' column - all attempts are logged permanently
-- DEPENDENCIES: None
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
-- LEVEL 1 TABLES (Depend on Level 0)
-- ========================================

-- ========================================
-- TABLE 11: form_fields
-- Field definitions for forms
-- HAS 'active' column for visibility control
-- DEPENDENCIES: forms
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
-- TABLE 12: form_submissions
-- Form submission records
-- NO 'active' column - all submissions are kept for history
-- DEPENDENCIES: forms
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
-- TABLE 13: admin_sessions
-- Persistent admin session storage
-- NO 'active' column - sessions are either valid or deleted
-- DEPENDENCIES: admin_users
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
-- TABLE 14: admin_action_logs
-- Audit log for admin actions
-- NO 'active' column - all actions are logged permanently
-- DEPENDENCIES: admin_users
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
-- LEVEL 2 TABLES (Depend on Level 1)
-- ========================================

-- ========================================
-- TABLE 15: form_submission_values
-- Normalized field values for form submissions
-- NO 'active' column - all values are kept for history
-- Allows efficient querying of individual field values
-- DEPENDENCIES: form_submissions, form_fields
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
-- TABLE 16: orders
-- Stores customer orders and contact form submissions
-- NO 'active' column - all orders are kept for history
-- Supports integration with dynamic forms system
-- DEPENDENCIES: form_submissions
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
    
    -- Form integration
    form_submission_id INT NULL,
    form_slug VARCHAR(255) NULL,
    
    -- Status tracking
    status ENUM('new', 'processing', 'completed', 'cancelled') DEFAULT 'new',
    archived_at TIMESTAMP NULL COMMENT 'When order was archived',
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
    INDEX idx_archived_at (archived_at),
    INDEX idx_created_at (created_at),
    INDEX idx_type (type),
    INDEX idx_form_slug (form_slug),
    INDEX idx_form_submission_id (form_submission_id),
    
    FOREIGN KEY (form_submission_id) REFERENCES form_submissions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- LEVEL 3 TABLES (Depend on Level 2 and Level 0)
-- ========================================

-- ========================================
-- TABLE 17: order_status_history
-- Tracks status changes for orders
-- NO 'active' column - all history is kept permanently
-- DEPENDENCIES: orders, admin_users
-- ========================================
CREATE TABLE IF NOT EXISTS order_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    old_status VARCHAR(50) NULL COMMENT 'Previous status (NULL for initial status)',
    new_status VARCHAR(50) NOT NULL COMMENT 'New status',
    changed_by INT NULL COMMENT 'Admin user ID who made the change',
    comment TEXT NULL COMMENT 'Optional comment about the status change',
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_order_id (order_id),
    INDEX idx_new_status (new_status),
    INDEX idx_changed_by (changed_by),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TABLE 18: order_notes
-- Internal notes for orders
-- NO 'active' column - all notes are kept permanently
-- DEPENDENCIES: orders, admin_users
-- ========================================
CREATE TABLE IF NOT EXISTS order_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    note TEXT NOT NULL,
    created_by INT NULL COMMENT 'Admin user ID who created the note',
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_order_id (order_id),
    INDEX idx_created_by (created_by),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Schema Creation Complete
-- ========================================
-- All 18 tables created successfully in proper dependency order!
--
-- DEPENDENCY TREE:
-- Level 0 (No dependencies):
--   - admin_users, settings, services, portfolio, testimonials, faq, 
--     content_blocks, forms, settings_audit, admin_login_attempts
-- Level 1 (Depend on Level 0):
--   - form_fields → forms
--   - form_submissions → forms
--   - admin_sessions → admin_users
--   - admin_action_logs → admin_users
-- Level 2 (Depend on Level 1):
--   - form_submission_values → form_submissions, form_fields
--   - orders → form_submissions
-- Level 3 (Depend on Level 2 and Level 0):
--   - order_status_history → orders, admin_users
--   - order_notes → orders, admin_users
--
-- Next Steps:
-- 1. Verify tables: SHOW TABLES;
-- 2. Check table count: SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE();
-- 3. Verify foreign keys: 
--    SELECT TABLE_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME 
--    FROM information_schema.KEY_COLUMN_USAGE 
--    WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL;
-- 4. Run seed script: Visit /api/init-database.php
-- 5. Create first admin: php scripts/create-admin.php
-- ========================================