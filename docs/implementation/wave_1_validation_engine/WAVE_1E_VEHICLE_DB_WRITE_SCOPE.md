# WAVE 1E — Vehicle DB Write Scope

**Wave:** IMPLEMENTATION WAVE 1E — Owner-approved DB-write Activation for Vehicle Create v2  
**Date:** 2026-06-23  
**Status:** IMPLEMENTED

---

## Objective

Activate controlled database write for **Vehicle Create v2 only**, after Waves 1A–1D.

**Flow:** UI → Validation Engine → Controlled Submit → `erp_vehicles` → Audit/History

---

## DB Foundation Inspection

| Item | Finding |
|------|---------|
| **Vehicle table** | `dbo.erp_vehicles` — confirmed in `erp-customer-vehicle-create.php` |
| **Phone/binding** | Not required for standalone vehicle v2 create in Wave 1E |
| **DB connection** | `customer_core_db()` → `erp_auth_create_local_odbc_connection()` |
| **Audit/history** | `dbo.erp_customer_core_history` via `customer_core_insert_history()` |
| **Decision** | **DB_WRITE_ACTIVATED_FOR_VEHICLE_V2** when schema confirmed at runtime |

---

## Field Mapping

| v2 clean field | Storage |
|----------------|---------|
| `plate` (structured) | `plate_number` as `{province}{letter}{number}-{series}` (normalized) |
| `vin` | `vin` |
| `chassis_no` | `chassis_number` if column exists, else notes |
| `engine_no` | `engine_number` if column exists, else notes |
| `brand_id` / `model_id` / `vehicle_class` | `brand`/`model` placeholders + notes metadata |
| `vehicle_notes` | `notes` (composed) |

---

## Still Active / Disabled

| Form | DB Write |
|------|----------|
| Customer Create v2 | **Active** (Wave 1D) |
| Vehicle Create v2 | **Activated** (Wave 1E) |
| JobCard Create v2 | **Disabled** (Wave 1C) |

---

## Next Step

**WAVE 1F** — JobCard Create v2 DB-write Activation

---

**END OF SCOPE**
