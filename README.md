# Gadget 50

Native PHP + MySQL public news platform for shared hosting and cPanel.

## Installation

1. Upload the repository to the web root.
2. Create an empty MySQL database and user.
3. Open `install.php` and enter the database and Super Admin details.
4. The installer creates the schema, seeds defaults, writes `config.php`, and creates `install.lock`.
5. Confirm that `config.php`, `install.lock`, and the `database/` directory are not publicly downloadable. The included Apache rules provide an additional safeguard.
6. After installation, remove or deny web access to `install.php`.

## Included features

- Dynamic installation wizard with password hashing, transactional schema setup, and a one-time lock file.
- Public news listing, category filters, detail pages, and view counts.
- Member registration/login and moderated news submission.
- Anonymous public author display while retaining the submitter for admins.
- Super Admin dashboard, settings, menus, categories, users, and news moderation/editing.
- PDO prepared statements, escaped output, sessions, CSRF-protected state-changing forms, role checks, and protected logout.
- Apache hardening rules for directory listing, sensitive files, and common security headers.

## Deployment notes

- PHP 8.0+ and PDO MySQL are recommended.
- Ensure the installer can temporarily write `config.php` and `install.lock`.
- Keep `config.php` outside public downloads when possible, or configure the server to serve PHP files only.
- Use HTTPS in production and keep regular database backups.
- Set restrictive permissions after installation; the web server only needs write access during setup.
- Do not commit real database credentials or a generated `config.php` to source control.
- If the server is Nginx or IIS, configure equivalent deny rules for `config.php`, `install.lock`, and `database/` because `.htaccess` is Apache-specific.
