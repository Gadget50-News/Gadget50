# Database migrations

Fresh installations create the complete current schema through the browser installer. Existing installations use an idempotent migration ledger and safe `CREATE TABLE IF NOT EXISTS`/column checks where supported.

Before upgrading:

1. Back up the database and uploaded files.
2. Test on a staging copy.
3. Upload the files and open the site in a browser.
4. Confirm the migration result in the administrator diagnostics.

Do not run raw SQL commands for normal installation. Manual SQL remains an emergency recovery option only when a host's PHP/database permissions prevent the browser installer from completing.
