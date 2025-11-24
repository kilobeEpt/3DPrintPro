-- ========================================
-- Content API Overhaul Migration
-- Adds slug, featured, and media fields to content tables
-- Version: 1.0
-- Date: January 2025
-- ========================================

-- Add slug and featured to Portfolio table
ALTER TABLE portfolio 
ADD COLUMN IF NOT EXISTS slug VARCHAR(255) NULL AFTER title,
ADD COLUMN IF NOT EXISTS featured BOOLEAN DEFAULT FALSE AFTER active,
ADD COLUMN IF NOT EXISTS image_path VARCHAR(500) NULL AFTER image_url,
ADD COLUMN IF NOT EXISTS image_size INT NULL COMMENT 'File size in bytes',
ADD COLUMN IF NOT EXISTS image_mime VARCHAR(100) NULL COMMENT 'MIME type';

-- Add index for slug (will be unique after backfill)
CREATE INDEX IF NOT EXISTS idx_portfolio_slug ON portfolio(slug);
CREATE INDEX IF NOT EXISTS idx_portfolio_featured ON portfolio(featured);

-- Add slug and featured to Testimonials table
ALTER TABLE testimonials
ADD COLUMN IF NOT EXISTS slug VARCHAR(255) NULL AFTER name,
ADD COLUMN IF NOT EXISTS featured BOOLEAN DEFAULT FALSE AFTER active,
ADD COLUMN IF NOT EXISTS avatar_path VARCHAR(500) NULL AFTER avatar,
ADD COLUMN IF NOT EXISTS avatar_size INT NULL COMMENT 'File size in bytes',
ADD COLUMN IF NOT EXISTS avatar_mime VARCHAR(100) NULL COMMENT 'MIME type';

-- Add index for slug (will be unique after backfill)
CREATE INDEX IF NOT EXISTS idx_testimonials_slug ON testimonials(slug);
CREATE INDEX IF NOT EXISTS idx_testimonials_featured ON testimonials(featured);

-- Add slug to FAQ table for better URL handling
ALTER TABLE faq
ADD COLUMN IF NOT EXISTS slug VARCHAR(255) NULL AFTER question;

-- Add index for slug
CREATE INDEX IF NOT EXISTS idx_faq_slug ON faq(slug);

-- Add slug to ContentBlock table (block_name is already unique, but slug helps with URLs)
ALTER TABLE content_blocks
ADD COLUMN IF NOT EXISTS slug VARCHAR(255) NULL AFTER block_name;

-- Add index for slug
CREATE INDEX IF NOT EXISTS idx_content_blocks_slug ON content_blocks(slug);

-- ========================================
-- Data Backfill (generate slugs from existing records)
-- ========================================

-- Generate slugs for Portfolio items (based on title)
UPDATE portfolio 
SET slug = LOWER(TRIM(REGEXP_REPLACE(
    REGEXP_REPLACE(title, '[^a-zA-Z0-9\\s-]', ''),
    '[\\s-]+', '-'
)))
WHERE slug IS NULL OR slug = '';

-- Generate slugs for Testimonials (based on name + id for uniqueness)
UPDATE testimonials
SET slug = CONCAT(
    LOWER(TRIM(REGEXP_REPLACE(
        REGEXP_REPLACE(name, '[^a-zA-Z0-9\\s-]', ''),
        '[\\s-]+', '-'
    ))),
    '-',
    id
)
WHERE slug IS NULL OR slug = '';

-- Generate slugs for FAQ items (based on question, truncated to first 100 chars)
UPDATE faq
SET slug = LOWER(TRIM(REGEXP_REPLACE(
    REGEXP_REPLACE(SUBSTRING(question, 1, 100), '[^a-zA-Z0-9\\s-]', ''),
    '[\\s-]+', '-'
)))
WHERE slug IS NULL OR slug = '';

-- Generate slugs for Content Blocks (use block_name)
UPDATE content_blocks
SET slug = block_name
WHERE slug IS NULL OR slug = '';

-- ========================================
-- Make slugs NOT NULL and UNIQUE after backfill
-- ========================================

-- For Portfolio
ALTER TABLE portfolio 
MODIFY COLUMN slug VARCHAR(255) NOT NULL;

-- Add unique constraint for portfolio slug
ALTER TABLE portfolio 
ADD CONSTRAINT unique_portfolio_slug UNIQUE (slug);

-- For Testimonials
ALTER TABLE testimonials
MODIFY COLUMN slug VARCHAR(255) NOT NULL;

-- Add unique constraint for testimonials slug
ALTER TABLE testimonials
ADD CONSTRAINT unique_testimonials_slug UNIQUE (slug);

-- For FAQ
ALTER TABLE faq
MODIFY COLUMN slug VARCHAR(255) NOT NULL;

-- Add unique constraint for faq slug
ALTER TABLE faq
ADD CONSTRAINT unique_faq_slug UNIQUE (slug);

-- For Content Blocks (keep block_name as primary unique identifier)
ALTER TABLE content_blocks
MODIFY COLUMN slug VARCHAR(255) NOT NULL;

-- Add unique constraint for content_blocks slug
ALTER TABLE content_blocks
ADD CONSTRAINT unique_content_blocks_slug UNIQUE (slug);

-- ========================================
-- Migration Complete
-- ========================================
