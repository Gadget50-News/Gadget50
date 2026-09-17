# Repository audit notes

The following source-level defects were corrected in the latest commit:

- `forgot-password.php` referenced `bootstrap.php` from the repository root even though the file is under `includes/`; this caused a fatal include error on password-reset requests.
- The email/2FA migration file had PHP verification-endpoint code in a `.sql` file; it is now valid SQL.
- The custom 404 endpoint is database-independent and no longer recursively includes itself.
- The root rewrite configuration preserves real files and folders and defines the application 404 document.

Before deployment, run the migration once on a backup/staging database and run `php -l` on every PHP file. Do not rerun the `ALTER TABLE` statements after they have succeeded unless they are adapted for the specific MySQL/MariaDB version.
