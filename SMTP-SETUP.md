# SMTP setup

Email starts **OFF** after installation. This prevents a broken or unconfigured mail server from breaking login, registration, or the public site.

## Gmail

1. Enable 2-Step Verification on the Gmail account.
2. Create a Gmail App Password; do not use the normal account password.
3. In **Admin → Site Settings**, choose Gmail:
   - Host: `smtp.gmail.com`
   - Port: `587`
   - Encryption: TLS
   - Username: the full Gmail address
   - Password: the 16-character App Password
4. Set a verified From address, save, test, then activate Email Service.

Port 465 with SSL is supported where the host blocks 587.

## Zoho

Choose Zoho and confirm the SMTP host for the specific Zoho region/account. Use the full mailbox address and a Zoho app-specific password where required. Common defaults are `smtp.zoho.com`, port 587, TLS, but the dashboard values remain editable.

## Custom SMTP

Enter the host, port, encryption (`TLS`, `SSL`, or no encryption only when the provider explicitly supports it), username, app password, From address, and From name.

The password is never displayed back in the form or included in public errors. Save, run **Test SMTP**, and activate only after a successful test. Only one provider can be active at a time.
