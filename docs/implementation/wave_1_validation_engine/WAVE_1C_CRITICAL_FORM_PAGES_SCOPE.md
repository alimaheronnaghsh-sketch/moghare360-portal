# WAVE 1C — Critical Form Pages Scope

**Wave:** IMPLEMENTATION WAVE 1C — Controlled Migration of Actual Selected Form Pages  
**Date:** 2026-06-23  
**Status:** IMPLEMENTED (validation-first preview; DB write disabled)

---

## Objective

Create controlled ERP v2 form pages and submit handlers for:

| Form | Rule Key | Form Page | Submit Handler |
|------|----------|-----------|----------------|
| Customer Create v2 | `customer_create_v2` | `erp-customer-create-v2.php` | `submit-customer-v2.php` |
| Vehicle Create v2 | `vehicle_create_v2` | `erp-vehicle-create-v2.php` | `submit-vehicle-v2.php` |
| JobCard Create v2 | `jobcard_create_v2` | `erp-jobcard-create-v2.php` | `submit-jobcard-v2.php` |

**Locked flow:** UI → Validation Engine → Controlled Submit → Preview Result (no DB in 1C)

---

## Components

| File | Purpose |
|------|---------|
| `erp-critical-forms-v2-live-preview.php` | Index linking forms + test pages |
| `tools/test-wave-1c-critical-form-pages.php` | CLI structural/integration checks |

Uses WAVE 1B bridge: `moghare360-form-validation-bridge.php`

---

## Wave 1C Behavior

- Persian RTL form UI
- POST → validate via bridge → show errors OR preview success + cleaned payload
- **DB writes intentionally not activated**
- No `config.php`, auth, or permission dependency
- Legacy/customer portal files **not modified**

### Preview defaults (fields not yet on UI)

| Form | Hidden / submit defaults |
|------|-------------------------|
| Customer | `customer_channel=walk_in`, `customer_class=retail` |
| Vehicle | `brand_id=10`, `model_id=25`, `vehicle_class=sedan` (hidden on form) |
| JobCard | `jobcard_type=repair`, `service_category=mechanical` (hidden) |

---

## Boundaries

| Constraint | Status |
|------------|--------|
| No legacy submit / portal files modified | ✅ |
| No SQL / schema change | ✅ |
| No auth/config/permission change | ✅ |
| Not committed / not pushed | ✅ |

---

## Product Boundary

- No public portal activation
- No official accounting / SaaS / payment gateway

---

## Next Step

**WAVE 1D** — Owner-approved DB-write activation for selected validated forms.

---

**END OF SCOPE**
