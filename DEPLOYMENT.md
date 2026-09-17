# Final deployment checklist

1. Back up the database and files.
2. Run `database/migrations/001_login_rate_limits.sql` and `database/migrations/002_email_verification_and_2fa.sql` on staging first.
3. Configure Zoho SMTP with an App Password, not the mailbox password.
4. Set the real HTTPS Site URL and verified From Email in Admin > Site Settings.
5. Test registration, verification, login, 2FA, reset, blocked reset, logout, and admin authorization.
6. Run `php -l` against every PHP file and inspect PHP/server error logs.
7. Confirm SMTP credentials are not committed to Git and are not exposed to users.
8. Enable HTTPS, secure cookies, backups, monitoring, and WAF/CDN protections.

This repository cannot perform a live SMTP, browser, or production-database test from GitHub. Do not claim completion until those checks pass on staging/live infrastructure.
