# MOGHARE360 V1 — Online Test Gate Report (P11.3)

## Current status

- Public domain: moghareh360.ir (cPanel)
- Laptop server: Apache/XAMPP port 8080 (placeholder endpoint in templates)
- Connectivity test passed; P11.3 adds HMAC-secured intake bridge

## Online test scope

- Website lead form → signed forwarder → laptop secure API → P1 `erp_customer_online_requests`
- Reception dashboard: `erp-reception-online-requests.php`
- **Not** full production go-live; controlled one-day online test gate

## What is secure

- HMAC-SHA256 on timestamp + raw JSON body
- Allowed source header validation
- Timestamp TTL (default 300s)
- Payload size limit
- Masked bridge logs
- Staff-only readiness page
- No customer-facing raw JSON

## What is not production yet

- No production deployment claim
- No OTP on simple lead form (reception reviews NEW requests)
- No P12 operational taxonomy
- Endpoint/IP configured only on server, not in Git

## Test path

1. cPanel `lead-form.php` → `forward-lead.php`
2. Laptop `api/online-intake-secure-receive.php`
3. P1 insert via `m360_online_req_insert`
4. Reception list verification

## PASS / WARNING / BLOCKED

| Status | Criteria |
|--------|----------|
| PASS | Secret configured, API exists, P1 persistence OK, templates present |
| WARNING | Private config missing on laptop, bridge disabled, log dir not writable |
| BLOCKED | Secret placeholder, API missing, P1 helper missing |

See `erp-online-test-readiness.php` (staff gate).
