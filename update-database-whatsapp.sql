-- Add verification_type column to otp_verifications table
ALTER TABLE otp_verifications 
ADD COLUMN verification_type ENUM('email', 'whatsapp', 'sms') DEFAULT 'email' AFTER otp;

-- Add phone column if it doesn't exist
ALTER TABLE otp_verifications 
ADD COLUMN phone VARCHAR(20) AFTER email;

-- Add index for faster lookups
CREATE INDEX idx_phone_otp ON otp_verifications(phone, otp, is_used);
CREATE INDEX idx_verification_type ON otp_verifications(verification_type);

-- Update existing records to have email as verification type
UPDATE otp_verifications SET verification_type = 'email' WHERE verification_type IS NULL;
