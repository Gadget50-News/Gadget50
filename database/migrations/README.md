# Login protection deployment note

The login limiter is persistent and server-side. It tracks a hashed IP key and a hashed device-cookie key.

Behavior:

- Attempts 1–2 show a generic invalid-credentials response.
- From attempt 2 onward, the form reports the failed count and remaining attempts.
- On the final remaining attempt, the form warns that another failure triggers a block.
- The fifth failed attempt sets `blocked_until` to 48 hours in the future.
- The block is checked before password verification, so a correct password cannot bypass it.
- A successful login clears the matching IP and device counters.
- The database stores hashes, not raw IP addresses or device tokens.

Apply the migration before deploying `login.php`:

```bash
mysql -u DATABASE_USER -p DATABASE_NAME < database/migrations/001_login_rate_limits.sql
```

Create a database backup before running the migration. This implementation treats the IP block as authoritative; therefore, users behind the same public IP can be affected by an IP-based lockout. Device cookies add a second server-side signal but cannot be considered an unchangeable phone identifier because users can clear cookies.
