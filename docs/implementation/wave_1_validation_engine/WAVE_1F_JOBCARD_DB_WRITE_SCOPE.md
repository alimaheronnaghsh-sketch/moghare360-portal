# WAVE 1F — JobCard DB Write Scope

**Wave:** IMPLEMENTATION WAVE 1F — Owner-approved DB-write Activation for JobCard Create v2  
**Date:** 2026-06-23  
**Status:** IMPLEMENTED

---

## Objective

Activate controlled database write for **JobCard Create v2** as the third DB-write activation in the validation wave sequence.

**Flow:** UI → Validation Engine → Controlled Submit → `erp_jobcards` → Audit/History

---

## DB Foundation Inspection

| Item | Finding |
|------|---------|
| **JobCard table** | `dbo.erp_jobcards` — confirmed in `erp-jobcard-create.php` |
| **Relations** | `dbo.erp_customer_vehicle_relations` — `relation_id` resolved when column exists |
| **DB connection** | `customer_core_db()` → `erp_auth_create_local_odbc_connection()` |
| **Audit/history** | `dbo.erp_jobcard_change_history` when table exists |
| **Reference validation** | `customer_id` / `vehicle_id` existence checked in `erp_customers` / `erp_vehicles`; active relation required when `relation_id` column exists |
| **Decision** | **DB_WRITE_ACTIVATED_FOR_JOBCARD_V2** when schema confirmed at runtime |

---

## Field Mapping

| v2 clean field | DB column |
|----------------|-----------|
| `customer_id` | `customer_id` |
| `vehicle_id` | `vehicle_id` |
| `reception_date` | `reception_at` (date + `00:00:00.000`) |
| `odometer` | `intake_mileage` |
| `complaint_text` | `customer_complaint` |
| `jobcard_type` / `service_category` / notes | `internal_notes` (composed) |
| — | `jobcard_status` = `RECEIVED` |
| — | `jobcard_number` = generated `V2J-…` |

---

## DB Write Status (All Critical Forms v2)

| Form | Status |
|------|--------|
| Customer Create v2 | Active (Wave 1D) |
| Vehicle Create v2 | Active (Wave 1E) |
| JobCard Create v2 | **Activated** (Wave 1F) |

---

## Boundaries

- No SQL / schema / auth / config / permission changes
- Customer and Vehicle v2 write behavior unchanged
- Not committed / not pushed
- **Cursor did not decide next roadmap step**

---

**END OF SCOPE**
