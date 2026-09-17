-- Run this migration on an existing Gadget50 database before deploying the login changes:
-- mysql -u USER -p DATABASE < database/migrations/001_login_rate_limits.sql
--
-- Behavior:
-- * Attempts are tracked server-side by a SHA-256 key for the client IP and submitted identity.
-- * The fifth failed attempt sets blocked_until to 48 hours in the future.
-- * A successful login clears both applicable counters.
-- * Errors remain generic so account existence is not disclosed.

# Login protection deployment note

The login limiter now requires the `login_rate_limits` table. Existing installations must run:

```bash
mysql -u DATABASE_USER -p DATABASE_NAME < database/migrations/001_login_rate_limits.sql
```

Do not remove old login records or user records. Take a database backup before applying the migration.
