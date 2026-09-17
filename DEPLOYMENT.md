# Final deployment policy

The source changes are complete for this implementation scope, but live infrastructure testing is not possible from GitHub.

Required before production:

1. Back up database and files.
2. Run both SQL migrations on staging, then production.
3. Configure Zoho with an App Password and a verified sender address.
4. Use Admin > Site Settings > Test SMTP.
5. Run PHP syntax checks: `find . -name '*.php' -print0 | xargs -0 -n1 php -l`.
6. Test registration, email verification, login, 2FA, lockout, password reset, blocked reset, logout, admin authorization, clean URLs and 404s.
7. Confirm HTTPS, secure cookies, error logging, file-upload protection, backups, WAF/CDN and monitoring.

The repository cannot honestly report a live test or a security percentage without the hosting environment and database. Report any staging error with its exact message and URL for the next fix.
