# Deployment

## cPanel/shared hosting

Upload the files with File Manager or SFTP, create a MySQL database/user, open `install.php`, and finish setup in the browser. Confirm that `config.php`, `install.lock`, and `database/` are denied by the server. Enable HTTPS and configure email from the dashboard.

## Localhost

Place the folder under XAMPP `htdocs`, WAMP `www`, or Laragon `www`, start Apache and MySQL, create an empty database in phpMyAdmin, and browse to the folder's `install.php` URL.

## InfinityFree/free hosting

The PHP application can be uploaded like any other shared-hosting site, but SMTP socket access, `.htaccess`, PHP extensions, and writable permissions vary. Run diagnostics first. If outbound SMTP is blocked, do not activate Email Service or email-based 2FA; use an allowed relay or another compatible host.

## Production checklist

- HTTPS enabled and secure cookies confirmed
- install.php denied after installation
- config.php and install.lock protected
- database and uploads backed up
- email tested before 2FA is enabled
- error logs enabled without displaying secrets
- staging test completed for registration, reset, login, 2FA, logout, uploads, routing, and 404
