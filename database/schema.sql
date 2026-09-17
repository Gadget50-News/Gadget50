-- Add this table to fresh installations as well as existing installations.
CREATE TABLE IF NOT EXISTS login_rate_limits (
    rate_key VARCHAR(150) NOT NULL PRIMARY KEY,
    failed_attempts INT UNSIGNED NOT NULL DEFAULT 0,
    blocked_until DATETIME NULL,
    last_failed_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

