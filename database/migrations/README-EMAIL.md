-- Adds email verification and password reset columns.
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS email_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER email,
    ADD COLUMN IF NOT EXISTS email_verification_token VARCHAR(128) NULL AFTER email_verified,
    ADD COLUMN IF NOT EXISTS email_verification_expires_at DATETIME NULL AFTER email_verification_token,
    ADD COLUMN IF NOT EXISTS password_reset_token VARCHAR(128) NULL AFTER email_verification_expires_at,
    ADD COLUMN IF NOT EXISTS password_reset_expires_at DATETIME NULL AFTER password_reset_token,
    ADD COLUMN IF NOT EXISTS two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER status,
    ADD COLUMN IF NOT EXISTS two_factor_secret VARCHAR(255) NULL AFTER two_factor_enabled;

-- Practical note: if you use existing accounts, you'll want to set email_verified = 1 for already-verified users before enforcing verification.
