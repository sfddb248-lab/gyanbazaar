-- Remove OTP Verification System
-- This script removes all OTP-related database structures

-- Drop OTP verifications table if it exists
DROP TABLE IF EXISTS otp_verifications;

-- Update users table - set all users as active and verified
UPDATE users SET status = 'active', email_verified = TRUE WHERE status = 'pending' OR email_verified = FALSE;

-- Remove OTP-related columns from users table
ALTER TABLE users DROP COLUMN IF EXISTS otp_code;
ALTER TABLE users DROP COLUMN IF EXISTS otp_expiry;

-- Success message
SELECT 'OTP system removed successfully. All users are now active and verified.' AS message;
