# Hosting compatibility

Gadget 50 is designed for ordinary PHP + MySQL hosting without a command-line requirement. It has been structured for cPanel, shared Apache hosting, XAMPP, WAMP, Laragon, and Linux PHP hosting.

## Provider limitations

- InfinityFree and similar free hosts may block outbound SMTP sockets, disable some PHP functions, or impose file/CPU limits. If SMTP is blocked, use the host's permitted mail relay or keep Email Service OFF; email-based 2FA cannot be enabled until delivery is validated.
- Apache clean URLs require `mod_rewrite`. Without it, use the `.php` entry points or configure an equivalent Nginx/IIS rule.
- Subfolder installs must retain the configured application URL; do not replace it with a domain-root URL.

Use the browser diagnostics in the admin area to check PHP, extensions, sessions, writable paths, database status, base URL, and mail status. Diagnostics intentionally do not show database or SMTP secrets.
