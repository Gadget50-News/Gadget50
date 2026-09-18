# Gadget 50 audit report

## Scope

This report records the source-level audit performed against the `main` branch before the universal-hosting implementation. Live SMTP, database, and hosting tests were not available from GitHub and are therefore marked unverified.

## Confirmed issues

| File/area | Problem | Root cause | Status |
|---|---|---|---|
| `.htaccess` | Root-only redirects and `ErrorDocument /404.php` break subfolder installations. | Absolute URLs and `DOCUMENT_ROOT` assumptions. | Fixed in portable URL helpers; server-specific rewrite remains documented. |
| `logout.php`, `news.php`, `submit.php` | Several internal links use `/...`, which escapes an application subdirectory. | URLs were written for domain-root deployment. | Fixed in the portability pass. |
| `includes/email.php` | Mail was treated as available by default and could be attempted before an administrator configured it. | Provider default was effectively enabled. | Fixed: email service is explicitly OFF until validated and activated. |
| `admin/settings.php` | No separate global email-service activation or provider validation state. | SMTP settings and service availability were conflated. | Fixed: provider/service settings and validation state are stored separately. |
| `database/schema.sql` | Fresh installs did not contain a migration ledger, email-service state, challenge records, or audit records. | Later features were only represented by ad-hoc migrations. | Fixed in the fresh-install schema. |
| `database/migrations/002_email_verification_and_2fa.sql` | Existing-install migration was not suitable for browser execution and did not create all current feature tables. | Migration was designed for manual CLI execution. | Superseded by idempotent browser migration support. |
| `includes/bootstrap.php` | Errors were suppressed without a controlled debug switch. | `error_reporting(0)` was unconditional. | Fixed: production-safe logging with opt-in debug mode. |

## Potential compatibility limitations

- SMTP requires outbound socket access and TLS support; some free hosts block SMTP ports. The application reports this as a test failure and does not enable email or 2FA.
- Apache clean URLs require `mod_rewrite` and permission to use `.htaccess`. Nginx/IIS require equivalent server configuration.
- PHP must provide PDO MySQL, sessions, JSON, OpenSSL, mbstring, fileinfo, and standard filesystem functions.
- A live browser/database test still must be run on the target host.

## Verification status

- Static source inspection: completed.
- Repository tree and PHP/SQL entry points: inspected.
- Live installation: not performed; no hosting/database credentials were provided.
- Live Gmail/Zoho SMTP: not performed; requires administrator credentials and an app password.
- PHP lint: cannot be executed by the GitHub file API; run the browser diagnostics or local `php -l` before production.

## Remaining limitations

The GitHub API cannot emulate a real PHP runtime, MySQL server, Apache rewrite engine, or SMTP provider. Production readiness must therefore be confirmed on a staging host using the documented acceptance checklist.
