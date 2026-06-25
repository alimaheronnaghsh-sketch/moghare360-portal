# MOGHARE360 V1 — First Real Customer Run

## Purpose

Controlled checklist for the **first non-test customer online request** after V1 lock. Uses mirror website → master API → SQL Server only.

## Preconditions

- [ ] `MOGHARE360_V1_CANONICAL_DATABASE_LOCK.md` applied and verified
- [ ] `private/erp-config.php` on master server (not in Git)
- [ ] `private/production-site-config.json` filled with real domain/URLs
- [ ] `VERIFY_PRODUCTION_RUNTIME_CONFIG.ps1` PASS
- [ ] At least one reception/owner user imported and login tested
- [ ] Mirror package points to correct `master_server_base_url`
- [ ] SSL certificate valid if `ssl_expected: true`

## Data path (locked)

```
Customer (moghareh360.ir)
  → mirror customer-request.php
  → POST /api/customer/request.php (master)
  → dbo.erp_customer_online_requests
  → optional review in ERP / reception workflow
```

**Forbidden:** `submit-customer.php`, legacy MySQL, local file drop on mirror host.

## Pre-flight (TEST markers only)

Automated verify uses synthetic payload only:

| Marker | Value |
|--------|--------|
| Customer name | `TEST_V1_REAL_RUN_READINESS_DO_NOT_USE` |
| Plate | `99-TEST-99` |
| Channel | `REAL_RUN_VERIFY` |

```powershell
.\tools\production\VERIFY_PRODUCTION_RUNTIME_CONFIG.ps1
```

Confirm row in SQL Server (optional):

```sql
SELECT TOP 5 online_request_id, customer_name, request_status, source_channel, created_at
FROM dbo.erp_customer_online_requests
WHERE customer_name LIKE 'TEST_V1_%'
ORDER BY online_request_id DESC;
```

## First real customer — execution steps

### 1. Owner sign-off window

- Confirm `erp-v1-production-signoff.php` shows installer/database/API READY.
- Note time and operator in operational log (outside Git if sensitive).

### 2. Mirror form check (no real data yet)

- Open mirror `customer-request.php` in browser.
- Confirm form fields: name, mobile, plate, request type, address/postal, odometer, etc.
- Submit **one final TEST** if needed; name prefix `TEST_V1_FINAL_`.

### 3. First real submission

- Reception or owner supervises first live submission.
- Customer completes mirror form once.
- Expected API response: HTTP 201 + Persian success message.
- **Do not** screenshot or paste real mobile/plate into GitHub issues.

### 4. Master DB confirmation

```sql
SELECT TOP 1
    online_request_id,
    customer_name,
    mobile,
    vehicle_plate,
    request_status,
    source_channel,
    request_type,
    LEN(request_payload_json) AS payload_len,
    created_at
FROM dbo.erp_customer_online_requests
WHERE customer_name NOT LIKE 'TEST_V1_%'
ORDER BY online_request_id DESC;
```

Verify:

- `company_id` = MOGHAREH_MAIN tenant
- `request_status` = `PENDING`
- `request_payload_json` contains legacy mirror fields when submitted

### 5. Operational handoff

- Reception acknowledges request in ERP workflow (existing screens).
- If workflow screen not yet used, track via `online_request_id` in internal log.
- Update `MOGHARE360_V1_POST_RUN_FIX_REGISTER.md` only with **non-PII** notes if issues found.

## Failure handling

| Symptom | Action |
|---------|--------|
| API 500 | Check ODBC, `private/erp-config.php`, SQL Server service |
| API 401/403 tenant | Verify `MOGHAREH_MAIN` company + domain mapping |
| Mirror cannot reach master | Fix `master_server_base_url`, firewall, SSL |
| Row missing in DB | Confirm API target is SQL Server not MySQL |

No destructive SQL. Additive fixes only.

## Post-run

- [ ] Mark first real customer run in owner operational acceptance (private log)
- [ ] Rotate any one-time passwords used during setup
- [ ] Keep `runtime/` reports local
- [ ] Run regression tests:

```powershell
C:\xampp\php\php.exe tools\test-v1-real-run-readiness.php
C:\xampp\php\php.exe tools\test-v1-canonical-database.php
C:\xampp\php\php.exe tools\test-v1-production-signoff.php
```

## PII rule

Real customer name, mobile, national ID, address, and plate **must not** appear in committed files, templates, tests, or runtime reports pushed to GitHub.
