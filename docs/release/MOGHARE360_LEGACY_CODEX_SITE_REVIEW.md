# MOGHARE360 — Legacy Codex Site Review

## Legacy ZIP Status

**FOUND** at:
`C:\Users\User\Documents\Codex\2026-05-18\you-are-my-senior-software-architect\cpanel_portal\moghareh360_portal_php.zip`

Extracted for review only to:
`tools/_legacy_codex_review/` (gitignored, not a runtime path)

## Files Inside ZIP

| File | Role |
|------|------|
| index.php | Landing — customer + staff links |
| config.php | MySQL PDO credentials (PLACEHOLDER) |
| customer.php | Customer registration form |
| submit-customer.php | POST → MySQL `portal_customers_staging` |
| service-request.php | Service request form |
| submit-service-request.php | POST → MySQL `portal_service_requests_staging` |
| admin-pending.php | Admin pending list + hardcoded password |
| assets/style.css | Dark RTL portal styles |

## Useful Items Extracted

| Item | Source | V1 Destination |
|------|--------|----------------|
| Tagline «پورتال یکپارچه خدمات خودرو» | index.php | mirror `index.php`, `customer-request.php` |
| Success message after service submit | submit-service-request.php | mirror `customer-request.php` alert |
| Customer fields: postal_address, extra_contact_info, job_title, birth_date | customer.php | mirror `customer-request.php` |
| Service field: odometer_km | service-request.php | mirror `customer-request.php` |
| Unified payload mapping (customer + vehicle + service) | both forms | mirror API payload + Master API |
| API field aliases: full_name, national_code, vehicle_brand, service_description | legacy names | `api/customer/request.php` |
| Button hover micro-interaction | style.css | `moghare360-v1-luxury-ui.css` (.m360-btn:hover) |

## Items Rejected

| Item | Reason |
|------|--------|
| config.php | MySQL cPanel credentials — forbidden; V1 uses private erp-config + ODBC |
| submit-customer.php | Direct MySQL write — incompatible with Mirror/SaaS V1 |
| submit-service-request.php | Direct MySQL write — incompatible |
| admin-pending.php | Hardcoded admin password + local MySQL — security risk |
| portal_customers_staging / portal_service_requests_staging | Legacy schema — not in V1 ERP |
| staff URL `http://IP-SERVER:7055/login` | Obsolete — V1 uses staff-login.php → Master API |
| Full style.css overwrite | V1 luxury UI already covers layout; only hover adopted |
| Two-step customer→service flow | Consolidated into single V1 form + one API call |

## Transferred to V1 (Files Modified)

| File | Change |
|------|--------|
| `release/moghare360-mirror-site-package/public_html/index.php` | Legacy tagline merged |
| `release/moghare360-mirror-site-package/public_html/customer-request.php` | Legacy fields + success copy + API payload |
| `release/moghare360-mirror-site-package/public_html/assets/css/moghare360-v1-luxury-ui.css` | Button hover from legacy |
| `public_html/api/customer/request.php` | Rich field mapping into service_note |

## Not Modified (By Design)

| File | Reason |
|------|--------|
| `public_html/index.php` | Legacy ERP portal (config.php) — separate from Mirror website; no overwrite |
| `public_html/config.php` | Forbidden sensitive file |
| No legacy submit pages copied | Must not activate direct DB routes |

## Config / Submit / API Alignment

| Check | Result |
|-------|--------|
| Legacy config.php copied? | **NO** |
| Legacy submit routes activated? | **NO** |
| Mirror form uses V1 API? | **YES** — `mirror_api_customer_request()` → `/api/customer/request.php` |
| API accepts legacy field names? | **YES** — aliases mapped server-side |
| UI improved? | **YES** — richer form + Persian success message + preserved V1 luxury UI |

## Security Confirmation

- No config.php copied into repo packages
- No credentials copied (legacy had CHANGE_ME placeholders only)
- No private config introduced
- No real customer data transferred
- Legacy extract folder gitignored
- admin-pending password not transferred

## Website Layer Source of Truth

V1 public website for moghareh360.ir = **Mirror Site Package**  
Master ERP portal at `public_html/index.php` remains legacy internal portal — not replaced.

## Review Date

2026-06-25
