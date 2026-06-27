# MOGHARE360 V1 — Domain / Host / Laptop Bridge Runbook

## Architecture

```
Customer → moghareh360.ir (cPanel form)
        → forward-lead.php (HMAC signed cURL)
        → LAPTOP_HOST_PLACEHOLDER:8080/api/online-intake-secure-receive.php
        → m360_online_req_insert → erp_customer_online_requests
        → erp-reception-online-requests.php
```

**No real static IP, passwords, or secrets in this document.**

## cPanel steps

1. Upload `deployment/cpanel/moghareh360/lead-form.php`
2. Copy `forward-lead.php.example` → `forward-lead.php` (server only)
3. Set on cPanel `forward-lead.php`:
   - `LAPTOP_ENDPOINT_URL` = `https://YOUR_LAPTOP_HOST:8080/api/online-intake-secure-receive.php`
   - `BRIDGE_SECRET` = long random string (match laptop config)
4. Test with DEMO data only

## Laptop steps

1. Create `private/m360-online-bridge-config.php` from example
2. Set `bridge_secret` (same as cPanel)
3. Set `bridge_enabled => true`
4. Ensure port forwarding to `:8080` is active
5. Open `erp-online-test-readiness.php` (staff login)
6. Submit DEMO test from moghareh360.ir
7. Verify reception dashboard shows NEW request

## Rollback

- Laptop: `bridge_enabled => false`
- cPanel: rename/remove `forward-lead.php`
- Optional: hide form or show maintenance message

## Logs

- Laptop: `private/logs/online-bridge/bridge-intake.log` (masked)
