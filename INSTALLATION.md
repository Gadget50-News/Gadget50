# Browser installation and upgrade guide

## Requirements

- PHP 8.0 or newer
- PDO and PDO MySQL
- OpenSSL, JSON, mbstring, fileinfo, and sessions
- MySQL 5.7+/8.x or a compatible MariaDB release
- A writable application directory for `config.php`, `install.lock`, and uploads

## Fresh installation

1. Upload the repository to a domain root or subfolder.
2. Create an empty database and database user in cPanel, your host panel, XAMPP, WAMP, or equivalent.
3. Open `install.php` in a browser.
4. Complete the environment check and enter database and Super Admin details.
5. The installer creates the complete schema and records its migration state.
6. Configure email later from **Admin → Site Settings**; SMTP is intentionally OFF after installation.
7. Delete or deny access to `install.php` after the lock file is created.

No SSH, Composer, Node.js, or database CLI is required for a normal fresh installation.

## Upgrades

Back up files and the database first. Open the site after uploading the new files; the installer/upgrade checks apply safe, idempotent schema changes. Never delete `config.php` or `install.lock` during an upgrade.

## Recovery

If an upgrade fails, keep the error reference shown by the installer, restore the file/database backup, and fix the reported requirement before retrying. Do not mark an installation complete when a required table or column failed to create.
