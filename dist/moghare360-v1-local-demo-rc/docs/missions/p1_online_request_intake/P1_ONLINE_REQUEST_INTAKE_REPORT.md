# MOGHARE360 P1 — Online Request Intake Report

## Schema discovery

| Role | Table |
|------|-------|
| Online requests | `dbo.erp_customer_online_requests` |
| Customers | `dbo.erp_customers` |
| Phones | `dbo.erp_customer_phones` |
| Vehicles | `dbo.erp_vehicles` |
| Relations | `dbo.erp_customer_vehicle_relations` |
| JobCard | `dbo.erp_jobcards` |
| JobCard history | `dbo.erp_jobcard_change_history` |

### Existing columns (before P1 migration)

- `erp_customer_online_requests`: `online_request_id`, `company_id`, `customer_name`, `mobile`, `vehicle_plate`, `service_note`, `request_status` (default `PENDING`), `source_channel`, `created_at`, `request_type`, `request_payload_json`
- **Missing before P1:** `customer_id`, `vehicle_id`, `converted_jobcard_id`, `visit_date`, `updated_at`, `otp_verified`
- **No dedicated online-request history table** before P1

### Status model

- Legacy DB default: `PENDING` (treated as **NEW** in reception filters)
- P1 workflow statuses: `NEW`, `UNDER_REVIEW`, `ACCEPTED`, `CONVERTED_TO_JOBCARD`, `REJECTED`
- JobCard initial status on convert: `RECEIVED` (via `moghare360_jobcard_v2_write`)

## SQL migration

**File:** `database/migrations/P1_online_request_intake.sql`

- Adds nullable columns to `erp_customer_online_requests` (idempotent `IF COL_LENGTH`)
- Creates `erp_customer_online_request_history` if missing
- **Non-destructive** — no DROP, no DELETE, no data wipe

Run on SQL Server before using full P1 columns/history in production.

## Files added/modified

### Added
- `public_html/includes/m360-online-request-helper.php`
- `public_html/includes/m360-reception-helper.php`
- `public_html/erp-reception-online-requests.php`
- `public_html/erp-reception-online-request-detail.php`
- `public_html/erp-reception-online-request-accept.php`
- `database/migrations/P1_online_request_intake.sql`
- `tools/test-p1-online-request-intake.php`
- `tools/test-p1-reception-dashboard.php`
- `tools/test-p1-online-request-to-jobcard.php`

### Modified
- `public_html/api/customer/request.php` — intake via helper, `NEW` status, entity resolve, `PUBLIC_SITE`
- `public_html/api/customer/profile-status.php` — returns `customer_id` for returning customers

## Online request intake behavior

1. OTP verified mobile enforced (`m360_otp_is_verified`)
2. Resolves existing `customer_id` / `vehicle_id` by mobile and plate (no duplicate ERP profile for returning customers)
3. Stores request with status `NEW`, source `PUBLIC_SITE`, `otp_verified=1` in payload/column
4. Returns `profile_required` when no ERP customer match
5. Mirror log + API log unchanged

## Reception dashboard behavior

- `erp-reception-online-requests.php` — filtered list, newest first, Persian RTL
- Staff session required (`erp_auth_current_user_id` via `m360_reception_require_staff`) — auth core unchanged
- Links to detail page per request

## Detail page behavior

- Read-only GET display of customer, vehicle, visit date, description, status
- POST actions (CSRF protected) to accept handler only
- Shows JobCard link when converted

## Convert to JobCard behavior

- Idempotent: existing `converted_jobcard_id` returns same card
- Creates customer/vehicle/relation if needed (v2 write helpers + safe fallback insert)
- Creates JobCard via `moghare360_jobcard_v2_write` with `RECEIVED`
- Updates request to `CONVERTED_TO_JOBCARD` + `converted_jobcard_id`
- Writes `erp_customer_online_request_history` when table exists

## Audit / history

Events: `ONLINE_REQUEST_CREATED`, `ONLINE_REQUEST_UNDER_REVIEW`, `ONLINE_REQUEST_ACCEPTED`, `ONLINE_REQUEST_CONVERTED_TO_JOBCARD`, `ONLINE_REQUEST_REJECTED`

## Security

- No credentials in repo
- No `mirror-config.php` real file committed
- No SQL destructive statements
- No Auth/Login file changes
- No production fake OTP

## Deploy note

1. Run `database/migrations/P1_online_request_intake.sql` on ERP SQL Server
2. Deploy new PHP files to ERP host (not only cPanel mirror package)
3. Reception URL: `erp-reception-online-requests.php`

SMS API key must be rotated if previously exposed in chat.

---

MOGHARE360 P1 connects online customer requests to the reception dashboard and supports controlled conversion into JobCards without changing auth core or destructive schema.
