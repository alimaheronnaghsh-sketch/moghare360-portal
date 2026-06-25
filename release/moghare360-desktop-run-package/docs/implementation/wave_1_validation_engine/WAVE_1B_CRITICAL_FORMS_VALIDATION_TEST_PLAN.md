# WAVE 1B — Critical Forms v2 Validation Test Plan

**Wave:** IMPLEMENTATION WAVE 1B  
**Date:** 2026-06-23

---

## Test Objectives

1. Verify form validation bridge wraps WAVE 1A engine + Critical Forms v2 registry.
2. Confirm Customer / Vehicle / JobCard v2 payloads validate as expected.
3. Confirm error summary and HTML rendering for Persian RTL UX.
4. Confirm unknown form keys are rejected safely.
5. Confirm optional fields do not block valid submissions.
6. Confirm **no database read/write** in test harnesses.

---

## Test Harnesses

| Harness | Path | Environment |
|---------|------|-------------|
| CLI | `php tools/test-wave-1b-critical-forms-validation.php` | Repo root |
| Browser | `http://localhost:8080/moghare360/erp-critical-forms-v2-validation-test.php` | XAMPP (copy `public_html` assets to htdocs if needed) |

Shared cases: `public_html/includes/moghare360-form-validation-bridge-test-cases.php`

---

## Test Cases (10)

| # | Case | Expected |
|---|------|----------|
| 1 | Valid `customer_create_v2` payload | `ok === true` |
| 2 | Invalid `customer_create_v2` payload | `moghare360_validation_has_failed()` true |
| 3 | Valid `vehicle_create_v2` payload | `ok === true` |
| 4 | Invalid `vehicle_create_v2` payload | has failed |
| 5 | Valid `jobcard_create_v2` payload | `ok === true` |
| 6 | Invalid `jobcard_create_v2` payload | has failed |
| 7 | Error summary rendering | Non-empty Persian summary for invalid customer |
| 8 | HTML error rendering | `<ul>` / `<li>` structure |
| 9 | Unknown form key rejection | `unknown_form_key` rule |
| 10 | Optional field behavior | Empty `national_id` + `notes` still valid |

---

## Pass Criteria

- CLI: exit code `0`, final line `WAVE 1B CRITICAL FORMS VALIDATION TEST PASSED`
- Browser: overall status **PASS**, 10/10 cases green
- No SQL execution
- No production form POST

---

## Out of Scope (Wave 1B)

- Live ERP submit page POST tests
- Workflow engine state transitions
- Audit log writes
- Database integration tests

---

## Post-Wave 1B (Wave 1C)

- Map confirmed ERP submit handlers to v2 payload shape
- Call bridge before existing DB/workflow logic
- Owner-approved form page migration only

---

**END OF TEST PLAN**
