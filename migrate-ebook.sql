-- Migration script to add ebook features to existing database

-- Add new columns to products table if they don't exist
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS product_type ENUM('digital', 'ebook', 'course') DEFAULT 'digital' AFTER category_id,
ADD COLUMN IF NOT EXISTS preview_pages INT DEFAULT 0 AFTER screenshots,
ADD COLUMN IF NOT EXISTS total_pages INT DEFAULT 0 AFTER preview_pages;

-- Update existing products to have default values
UPDATE products SET product_type = 'digital' WHERE product_type IS NULL;
UPDATE products SET preview_pages = 0 WHERE preview_pages IS NULL;
UPDATE products SET total_pages = 0 WHERE total_pages IS NULL;
