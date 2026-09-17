# Gadget 50

A production-ready PHP + MySQL news platform starter built with native PHP and designed for shared hosting.

## Installation

1. Upload the project files to your hosting root or subdirectory.
2. Open `install.php` in a browser.
3. Enter the MySQL host, database name, credentials, and super admin details.
4. The installer will create the database tables, insert default settings, and generate `config.php`.
5. After installation, open `index.php` to view the public front-end.

## Notes

- The installer locks itself after successful setup using `install.lock`.
- Database access uses PDO with prepared statements.
- The app includes a public-facing homepage starter and database-ready foundation for the full admin panel and user news workflow.
