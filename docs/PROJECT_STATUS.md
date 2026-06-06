# MOGHARE360 Portal - Project Status

## Current Repository Status

This repository contains the sanitized source code of the MOGHARE360 portal.

Real production credentials have been removed.

## Important Rules

1. Do not commit real config.php.
2. Do not commit database passwords.
3. Do not commit SMS API keys.
4. Do not commit runtime logs.
5. Do not commit temporary backup files.
6. Do not change multiple core files at the same time unless documented.

## Main Working Folder

/public_html

## Key Files

- public_html/index.php
- public_html/customer-contract.php
- public_html/customer-profile.php
- public_html/customer-service-request.php
- public_html/send-otp.php
- public_html/verify-otp.php
- public_html/assets/style.css
- public_html/assets/app.js

## Contract Flow Goal

The target contract flow is:

1. Customer opens contract page.
2. Customer sees only request summary and contract view button.
3. Customer opens contract text.
4. Customer must tick contract-read confirmation.
5. Only then signature section appears.
6. Customer enters full name and national ID.
7. Customer signs digitally.
8. Three legal popups appear one by one:
   - Hidden/computer faults acceptance
   - Test drive / body insurance option
   - Purchase authorization limit
9. OTP is sent.
10. Customer enters OTP.
11. Contract becomes ONLINE_SIGNED.
12. JobCard can be activated.

## Next Development Rule

All contract changes must be done in this order:

1. Read current files.
2. Prepare patch.
3. Test locally or in staging.
4. Commit.
5. Push.
6. Upload to production only after manual approval.
