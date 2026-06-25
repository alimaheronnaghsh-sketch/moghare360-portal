# WAVE 1C — Critical Form Pages Test Plan

**Wave:** IMPLEMENTATION WAVE 1C  
**Date:** 2026-06-23

---

## CLI Test

**Command:** `php tools/test-wave-1c-critical-form-pages.php`

Checks:

- All Wave 1C PHP pages exist
- Validation bridge exists
- Each submit file includes bridge
- Each submit file contains DB-write disabled message
- No SQL in Wave 1C deliverables

**Pass:** exit code 0, `WAVE 1C CRITICAL FORM PAGES TEST PASSED`

---

## Browser — Index & Forms

| URL | Expected |
|-----|----------|
| `/erp-critical-forms-v2-live-preview.php` | Links to all v2 forms + test pages |
| `/erp-customer-create-v2.php` | RTL form, Validation First banner |
| `/erp-vehicle-create-v2.php` | Plate fields + optional VIN/chassis/engine |
| `/erp-jobcard-create-v2.php` | Numeric customer/vehicle IDs, no DB dropdown |

---

## Manual Submit Tests

| Case | Payload | Expected |
|------|---------|----------|
| Customer valid | Persian name + `09121234567` | Preview success + clean JSON |
| Customer invalid | Latin name / bad mobile | Persian validation errors, no DB |
| Vehicle valid | Plate `12-ب-345-67` + defaults | Preview success |
| Vehicle invalid | Empty plate / bad class | Validation errors |
| JobCard valid | IDs 100/200, date, complaint | Preview success |
| JobCard invalid | customer_id=0, empty complaint | Validation errors |

---

## Out of Scope

- Database insert/update
- Workflow engine transitions
- Audit log writes
- Auth/session gates

---

**END OF TEST PLAN**
