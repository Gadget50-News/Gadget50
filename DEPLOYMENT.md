# Gadget 50 deployment checklist

## Before upload

- Use PHP 8.0+ with PDO MySQL enabled.
- Create an empty MySQL database and a least-privilege database user in cPanel.
- Upload the repository contents over SFTP/HTTPS.
- Do not upload a real `config.php` or `install.lock` from another installation.

## Install

1. Visit `/install.php` once.
2. Enter the cPanel database host, database name, database user, and password.
3. Create the Super Admin account.
4. After the success redirect, verify `/login.php` and `/admin/`.
5. Confirm that visiting `/install.php` redirects or is denied.

## After install

- Enable HTTPS and verify the browser shows a secure connection.
- Change branding from **Admin → Site Settings**.
- Create a category and publish a test story.
- Test registration, anonymous submission, moderation, category filtering, detail pages, and logout.
- Configure cPanel backups and review error logs.
- Keep `config.php` and `install.lock` protected; never commit generated credentials.

Apache hardening is included in `.htaccess`. Nginx/IIS hosts need equivalent deny rules for `config.php`, `install.lock`, `install.php`, and `database/`.
