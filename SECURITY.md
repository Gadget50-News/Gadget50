# Security policy

Gadget 50 uses PDO prepared statements, CSRF tokens, password hashing, session regeneration after authentication, role checks, server-side login rate limits, secure random tokens, upload MIME validation, and protected sensitive directories.

Email credentials are administrator secrets. They are not rendered into HTML, logs, or public errors. Email service is OFF until a provider configuration has passed a test. Email-based 2FA is unavailable while the global service is OFF; there is no universal bypass code.

The application cannot guarantee security against a compromised host, weak database permissions, blocked TLS, stolen credentials, VPN/Tor abuse, or misconfigured web servers. Keep PHP, MySQL, and the hosting platform patched and use backups.
