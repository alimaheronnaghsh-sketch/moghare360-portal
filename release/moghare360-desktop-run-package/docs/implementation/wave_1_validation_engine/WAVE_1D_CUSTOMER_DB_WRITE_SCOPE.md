# WAVE 1D — Customer DB Write Scope

**Wave:** IMPLEMENTATION WAVE 1D — Owner-approved DB-write Activation for Customer Create v2  
**Date:** 2026-06-23  
**Status:** IMPLEMENTED

---

## Objective

Activate controlled database write for **Customer Create v2 only**, preserving validation-first flow:

**UI → Validation Engine → Controlled Submit → Database → Audit/History**

---

## DB Foundation Inspection

| Item | Finding |
|------|---------|
| **Customer table** | `dbo.erp_customers` — confirmed in `erp-customer-vehicle-create.php` and domain docs |
| **Phone table** | `dbo.erp_customer_phones` — optional secondary insert |
| **Audit/history** | `dbo.erp_customer_core_history` via `customer_core_insert_history()` |
| **DB connection** | `customer_core_db()` → `erp_auth_create_local_odbc_connection()` (existing local ODBC pattern) |
| **Decision** | **DB_WRITE_ACTIVATED_FOR_CUSTOMER_V2** when schema columns confirmed at runtime |

If `erp_customers` or required columns missing → `DB_WRITE_BLOCKED_SAFE_SCHEMA_NOT_CONFIRMED` (no fake success).

---

## Components

| File | Purpose |
|------|---------|
| `includes/moghare360-customer-v2-write-helper.php` | Prepared insert + optional phone + history |
| `submit-customer-v2.php` | Validate → write → redirect to result |
| `erp-customer-create-v2-result.php` | One-time session result display |
| `tools/test-wave-1d-customer-db-write.php` | CLI structural checks |

### Field mapping (clean → erp_customers)

| v2 clean field | DB column |
|----------------|-----------|
| `customer_name` | `full_name` |
| `mobile` | `primary_mobile` (+ `erp_customer_phones`) |
| `national_id` | `national_id` |
| `customer_channel` / `customer_class` / `notes` | composed `notes` |
| — | `customer_type` = `PERSON` |
| — | `lifecycle_state` = `ACTIVE` |
| — | `customer_code` = generated `V2C-…` |

---

## Still Disabled

| Form | DB Write |
|------|----------|
| Vehicle Create v2 | Disabled (Wave 1C message) |
| JobCard Create v2 | Disabled (Wave 1C message) |

---

## Boundaries

| Constraint | Status |
|------------|--------|
| No SQL files / schema change | ✅ |
| No auth/config/permission model change | ✅ |
| Legacy portal / staging files untouched | ✅ |
| Not committed / not pushed | ✅ |

---

## Next Step

**WAVE 1E** — Vehicle Create v2 DB-write Activation (if customer write verified in production/local)

If blocked at runtime: **WAVE 1D-FIX** — Confirm customer table/schema and DB helper

---

**END OF SCOPE**
