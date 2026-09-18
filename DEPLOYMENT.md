# Deployment status

The implementation work is on the `universal-hosting-email-2fa-audit` branch.

## Completed in this branch

- Browser-based fresh-install schema includes core CMS tables, authentication challenges, and audit logs.
- Email service is explicitly disabled until SMTP configuration passes a test.
- Gmail, Zoho, and custom SMTP settings are supported by the mail service layer.
- Login 2FA uses expiring, hashed challenge records and does not bypass failed email delivery.
- User dashboard includes per-user 2FA availability handling.
- Apache rewrite rules use relative substitutions so the project can be installed in a subfolder.
- Logout and generated application links use the centralized URL helper where the updated files support it.

## Required target-host verification

GitHub cannot run PHP, MySQL, Apache, or an SMTP provider. Before production, run the application on staging and verify:

1. Fresh browser installation and invalid database credentials.
2. Existing-install migration `database/migrations/003_email_service_and_auth_challenges.sql`.
3. Registration with Email Service OFF and ON.
4. Gmail App Password, Zoho SMTP, and custom SMTP test delivery.
5. Login with 2FA OFF, ON, invalid code, expired code, and SMTP failure.
6. Subfolder routing, clean URLs, logout, uploads, and the custom 404.
7. PHP syntax checks and secure permissions for `config.php`, `install.lock`, `database/`, and `uploads/`.

No live-host test or SMTP delivery result is claimed by this repository change.
