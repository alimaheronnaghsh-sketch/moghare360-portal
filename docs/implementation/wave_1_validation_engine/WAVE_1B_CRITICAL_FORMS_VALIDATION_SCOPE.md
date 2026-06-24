# WAVE 1B — Critical Forms v2 Validation Scope

**Wave:** IMPLEMENTATION WAVE 1B — Controlled Critical Forms v2 Validation Integration  
**Date:** 2026-06-23  
**Status:** IMPLEMENTED (bridge + test harness)

---

## Objective

Integrate the WAVE 1A validation engine into selected Critical Forms v2 in a controlled, non-destructive way:

**UI → Validation Engine → Workflow Engine / Existing Submit Logic → Database → Audit Log**

This wave proves the bridge layer; production submit pages are integrated only when safe matching files exist.

---

## Selected Forms (Rule Keys)

| Form | Rule Key |
|------|----------|
| Customer Create v2 | `customer_create_v2` |
| Vehicle Create v2 | `vehicle_create_v2` |
| JobCard Create v2 | `jobcard_create_v2` |

---

## Components Delivered

| File | Purpose |
|------|---------|
| `public_html/includes/moghare360-form-validation-bridge.php` | Reusable validation bridge API |
| `public_html/includes/moghare360-form-validation-bridge-test-cases.php` | Shared browser/CLI test cases |
| `public_html/erp-critical-forms-v2-validation-test.php` | Browser harness (no DB) |
| `tools/test-wave-1b-critical-forms-validation.php` | CLI harness |

### Bridge API

- `moghare360_validate_form_payload(string $formKey, array $payload): array`
- `moghare360_validation_has_failed(array $result): bool`
- `moghare360_validation_error_summary(array $result): string`
- `moghare360_validation_errors_as_html(array $result): string`
- `moghare360_validation_redirect_with_errors(string $returnUrl, array $result, array $oldInput = []): void`

Helper session pull functions for form pages after redirect:

- `moghare360_validation_pull_session_errors()`
- `moghare360_validation_pull_session_old_input()`

---

## Submit Page Integration (Allowed List Only)

Inspected allowed submit filenames:

| Allowed file | Exists | Integrated |
|--------------|--------|------------|
| `submit-customer.php` | No | Pending |
| `submit-vehicle.php` | No | Pending |
| `submit-jobcard.php` | No | Pending |
| `submit-customer-v2.php` | No | Pending |
| `submit-vehicle-v2.php` | No | Pending |
| `submit-jobcard-v2.php` | No | Pending |
| `submit-jobcard-create.php` | No | Pending |
| `submit-service-request.php` | Yes | **Not integrated** — customer portal staging flow; field map does not match `jobcard_create_v2`; would conflict with portal/auth boundary |

**Runtime submit integration is pending** until owner confirms target ERP submit pages (Wave 1C).

Related files that exist but are **outside** the allowed modify list (not touched):

- `submit-customer-entry.php`
- `submit-vehicle-binding.php`

---

## Boundaries

| Constraint | Status |
|------------|--------|
| No rewrite of existing business logic | ✅ |
| No database schema change | ✅ |
| No SQL files | ✅ |
| No auth / permission change | ✅ |
| No config change | ✅ |
| Existing submit pages not broken | ✅ (none modified) |
| Validation before submit when integrated | ✅ (bridge ready) |
| Fallback-safe bridge (unknown form key → structured error) | ✅ |
| Not committed / not pushed | ✅ |

---

## Product Boundary

- No public portal activation
- No official accounting activation
- No SaaS activation
- No payment gateway activation

---

## Next Step

**WAVE 1C** — Controlled migration of actual selected form pages after owner approval.

---

**END OF SCOPE**
