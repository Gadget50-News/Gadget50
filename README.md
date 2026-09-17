# Gadget 50

Native PHP + MySQL public news platform for shared hosting and cPanel.

## Installation

1. Upload the repository to the web root.
2. Create an empty MySQL database and user.
3. Open `install.php` and enter the database and Super Admin details.
4. The installer creates the schema, seeds defaults, writes `config.php`, and creates `install.lock`.
5. Confirm that `config.php`, `install.lock`, and the `database/` directory are not publicly downloadable.
6. After installation, remove or deny web access to `install.php`.

## Included features

- Dynamic installation wizard with password hashing and transactional schema setup.
- Public news listing, category filters, detail pages, anonymous author display, and view counts.
- Member registration/login and moderated news submission.
- Super Admin dashboard, settings, menus, categories, users, and news moderation/editing.
- PDO prepared statements, escaped output, secure sessions, CSRF-protected state-changing forms, role checks, and protected logout.
- Apache hardening rules for directory listing, sensitive files, and common security headers.

## Go-live checklist

- [ ] Use PHP 8.0+ with PDO MySQL enabled.
- [ ] Create a production MySQL database and least-privilege user.
- [ ] Upload over HTTPS/SFTP and run `install.php` once.
- [ ] Confirm `config.php` and `install.lock` are protected.
- [ ] Remove or deny access to `install.php` after installation.
- [ ] Enable HTTPS and verify secure session cookies.
- [ ] Log in as Super Admin and change branding/settings.
- [ ] Create a category and publish a test news item.
- [ ] Test registration, login, anonymous submission, moderation, logout, and category filtering.
- [ ] Configure database backups and review hosting error logs.

`.htaccess` rules apply to Apache. For Nginx or IIS, configure equivalent deny rules for `config.php`, `install.lock`, `install.php`, and `database/`.
