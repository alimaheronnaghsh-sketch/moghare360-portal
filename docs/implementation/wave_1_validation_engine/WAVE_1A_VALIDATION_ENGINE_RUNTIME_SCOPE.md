# WAVE 1A — Validation Engine Runtime Scope

**Wave:** IMPLEMENTATION WAVE 1A — Validation Engine Runtime Foundation  
**Status:** Implemented — test harness only  
**SQL:** Not required

---

## Objective

Implement the **runtime validation engine foundation** and **Critical Forms v2 rule registry** without modifying existing production forms.

---

## Deliverables

| Item | Path |
|------|------|
| Validation engine | `public_html/includes/moghare360-validation-engine.php` |
| Critical Forms v2 rules | `public_html/includes/moghare360-critical-form-v2-rules.php` |
| Browser test | `public_html/erp-validation-engine-runtime-test.php` |
| CLI test | `tools/test-wave-1a-validation-engine.php` |

---

## Architecture

**UI → Validation Engine → Workflow Engine → Database → Audit Log**

Wave 1A implements the **Validation Engine** helper only — pure PHP, no DB/session/auth.

---

## Out of Scope (Wave 1A)

- Existing production form modification
- SQL / schema changes
- Auth / config / permission changes
- Official accounting, payment gateway, SaaS, public portal
- Deployment

---

## Next Step

**WAVE 1B** — Controlled integration of validation engine into selected Critical Forms v2.

---

**END OF SCOPE**
