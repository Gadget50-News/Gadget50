-- Existing active accounts are treated as already verified during migration.
-- Newly registered accounts explicitly receive email_verified = 0.
CREATE TABLE IF NOT EXISTS login_rate_limits (
    rate_key VARCHAR(150) NOT NULL PRIMARY KEY,
    failed_attempts INT UNSIGNED NOT NULL DEFAULT 0,
    blocked_until DATETIME NULL,
    last_failed_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS email_verified TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS email_verification_token VARCHAR(128) NULL,
    ADD COLUMN IF NOT EXISTS email_verification_expires_at DATETIME NULL,
    ADD COLUMN IF NOT EXISTS password_reset_token VARCHAR(128) NULL,
    ADD COLUMN IF NOT EXISTS password_reset_expires_at DATETIME NULL,
    ADD COLUMN IF NOT EXISTS two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS two_factor_secret VARCHAR(255) NULL;

UPDATE users SET email_verified = 1 WHERE status = 'active' AND email_verified = 0;
