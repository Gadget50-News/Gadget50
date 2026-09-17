# Gadget 50

A production-ready native PHP + MySQL news platform starter tailored for shared hosting and cPanel deployment.

## Installation

1. Upload these files to your hosting directory.
2. Visit `install.php` in a browser.
3. Enter your MySQL host, database, credentials, and the Super Admin account.
4. The installer will create the schema, seed defaults, and generate `config.php`.
5. After installation, open `index.php` to see the public homepage.

## Notes

- The installer locks itself using `install.lock` after a successful setup.
- All database queries use PDO prepared statements.
- Output is escaped with `htmlspecialchars()` to reduce XSS risk.
- This stage includes the installer, schema, config bootstrap, and the public homepage scaffold.
