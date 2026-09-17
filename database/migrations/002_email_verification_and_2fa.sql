-- Email verification, password reset, 2FA, and persistent login-lockout support.
-- Run this migration once on an existing installation after taking a backup.

CREATE TABLE IF NOT EXISTS login_rate_limits (
    rate_key VARCHAR(150) NOT NULL PRIMARY KEY,
    failed_attempts INT UNSIGNED NOT NULL DEFAULT 0,
    blocked_until DATETIME NULL,
    last_failed_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE users ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE users ADD COLUMN email_verification_token VARCHAR(128) NULL;
ALTER TABLE users ADD COLUMN email_verification_expires_at DATETIME NULL;
ALTER TABLE users ADD COLUMN password_reset_token VARCHAR(128) NULL;
ALTER TABLE users ADD COLUMN password_reset_expires_at DATETIME NULL;
ALTER TABLE users ADD COLUMN two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE users ADD COLUMN two_factor_secret VARCHAR(255) NULL;

UPDATE users SET email_verified = 1 WHERE status = 'active' AND email_verified = 0;
