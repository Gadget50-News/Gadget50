-- Run once on an existing installation after taking a database backup.
CREATE TABLE IF NOT EXISTS login_rate_limits (
    rate_key VARCHAR(150) NOT NULL PRIMARY KEY,
    failed_attempts INT UNSIGNED NOT NULL DEFAULT 0,
    blocked_until DATETIME NULL,
    last_failed_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET @db = DATABASE();

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='users' AND COLUMN_NAME='email_verified')=0, 'ALTER TABLE users ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 1', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='users' AND COLUMN_NAME='email_verification_token')=0, 'ALTER TABLE users ADD COLUMN email_verification_token VARCHAR(128) NULL', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='users' AND COLUMN_NAME='email_verification_expires_at')=0, 'ALTER TABLE users ADD COLUMN email_verification_expires_at DATETIME NULL', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='users' AND COLUMN_NAME='password_reset_token')=0, 'ALTER TABLE users ADD COLUMN password_reset_token VARCHAR(128) NULL', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='users' AND COLUMN_NAME='password_reset_expires_at')=0, 'ALTER TABLE users ADD COLUMN password_reset_expires_at DATETIME NULL', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='users' AND COLUMN_NAME='two_factor_enabled')=0, 'ALTER TABLE users ADD COLUMN two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='users' AND COLUMN_NAME='two_factor_secret')=0, 'ALTER TABLE users ADD COLUMN two_factor_secret VARCHAR(255) NULL', 'SELECT 1'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

UPDATE users SET email_verified = 1 WHERE status = 'active' AND email_verified = 0;
