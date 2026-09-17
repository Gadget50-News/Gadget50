# Security verification and deployment notes

## Login lockout behavior

The login endpoint now applies a server-side lockout to three hashed keys:

- the connecting IP address (`REMOTE_ADDR`)
- a long-lived, HttpOnly device cookie
- the submitted username/email identity

The lockout is checked before password verification. After five failed attempts, the matching keys receive a 48-hour `blocked_until` timestamp. A valid password cannot bypass an active lockout. Successful authentication clears the matching counters.

The login form reports remaining attempts after the second failure and warns on the fourth failure. A small progressive delay is applied to failed requests.

## Required deployment step

Existing installations must be backed up and then migrated:

```bash
mysql -u DATABASE_USER -p DATABASE_NAME < database/migrations/001_login_rate_limits.sql
```

Do not run this migration against production without a verified backup and a staging test first.

## Testing status

This repository session can inspect and commit source code, but it cannot perform a live browser/database test against your hosting server. Before merging, run PHP syntax checks, apply the migration on a staging database, and verify five failed attempts, the 48-hour block, correct-password rejection during the block, successful-login reset, logout, and protected-page access.

No honest security percentage can be guaranteed. The changes improve resistance to common brute-force attacks, but VPN/Tor/new-IP attacks, compromised devices, server vulnerabilities, dependency vulnerabilities, credential theft, and hosting misconfiguration remain outside this code-only control. Use HTTPS, MFA, a WAF/CDN, secure backups, patched PHP/MySQL, least-privilege database credentials, and monitored logs for stronger protection.
