# Gadget 50

A native PHP + MySQL news platform starter designed for shared hosting and cPanel deployment.

## Installation

1. Upload the files to your hosting directory.
2. Open `install.php` in the browser.
3. Enter MySQL credentials and the Super Admin account details.
4. The installer creates the database tables and writes `config.php`.
5. Open `index.php` to browse the public front-end.

## Notes

- The installer locks itself after a successful setup using `install.lock`.
- Database operations use PDO prepared statements.
- Outputs are escaped with `htmlspecialchars()`.
