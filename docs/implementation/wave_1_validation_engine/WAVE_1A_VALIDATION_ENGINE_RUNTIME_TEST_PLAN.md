# WAVE 1A — Validation Engine Runtime Test Plan

**SQL:** Not required

---

## CLI Tests (`tools/test-wave-1a-validation-engine.php`)

| # | Test |
|---|------|
| 1 | Valid Iranian mobile |
| 2 | Invalid Iranian mobile |
| 3 | Valid national ID (checksum) |
| 4 | Invalid national ID (repeated digits) |
| 5 | Valid VIN |
| 6 | Invalid VIN (I/O/Q) |
| 7 | Valid money amount |
| 8 | Invalid negative money |
| 9 | Valid yyyy-mm-dd date |
| 10 | Invalid date |
| 11 | Valid customer_create_v2 |
| 12 | Invalid customer_create_v2 |
| 13 | Valid vehicle_create_v2 |
| 14 | Invalid jobcard_create_v2 |
| 15 | Critical form keys registry (11 forms) |
| 16 | payment_tracking_preview_v2 allowed_value |

**Exit code:** 0 = all pass; 1 = any fail

---

## Browser Tests

**URL:** `http://localhost:8080/moghare360/erp-validation-engine-runtime-test.php`

Same cases as CLI — overall PASS/FAIL card + per-row results.

---

## Validation Rules Covered

required · persian_name · mobile_ir · national_id_ir · vin · iran_plate · engine_or_chassis · positive_number · money_amount · kilometer · safe_text · date_yyyy_mm_dd · allowed_value · optional

---

**END OF TEST PLAN**
