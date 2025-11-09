-- Update GyaanBazaar Branding in Database
-- Run this SQL in phpMyAdmin or MySQL command line

-- Update site name
UPDATE settings SET setting_value = 'GyaanBazaar' WHERE setting_key = 'site_name';

-- Add or update tagline
INSERT INTO settings (setting_key, setting_value) 
VALUES ('site_tagline', 'ज्ञान की दुकान, सबके लिए आसान')
ON DUPLICATE KEY UPDATE setting_value = 'ज्ञान की दुकान, सबके लिए आसान';

-- Add or update site description
INSERT INTO settings (setting_key, setting_value) 
VALUES ('site_description', 'India\'s Premier Digital Education Marketplace - Quality Notes & Video Courses')
ON DUPLICATE KEY UPDATE setting_value = 'India\'s Premier Digital Education Marketplace - Quality Notes & Video Courses';

-- Add or update site type
INSERT INTO settings (setting_key, setting_value) 
VALUES ('site_type', 'E-Learning Marketplace')
ON DUPLICATE KEY UPDATE setting_value = 'E-Learning Marketplace';

-- Add or update contact email
INSERT INTO settings (setting_key, setting_value) 
VALUES ('contact_email', 'support@gyaanbazaar.com')
ON DUPLICATE KEY UPDATE setting_value = 'support@gyaanbazaar.com';

-- Add or update support phone
INSERT INTO settings (setting_key, setting_value) 
VALUES ('support_phone', '+91-XXXXXXXXXX')
ON DUPLICATE KEY UPDATE setting_value = '+91-XXXXXXXXXX';

SELECT 'GyaanBazaar branding updated successfully!' as Status;
