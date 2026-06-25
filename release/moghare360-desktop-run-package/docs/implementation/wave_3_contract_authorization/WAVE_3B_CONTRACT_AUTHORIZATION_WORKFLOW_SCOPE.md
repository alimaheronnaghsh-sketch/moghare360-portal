# WAVE 3B — Contract Authorization Workflow Scope

**Wave:** IMPLEMENTATION WAVE 3B  
**Parent:** IMPLEMENTATION WAVE 3 — Contract Authorization Runtime  
**Date:** 2026-06-23

---

## Objective

Controlled approval workflow transitions for JobCard contract authorization records (WAVE 3A foundation).

Flow: **Authorization → Current Status → Controlled Transition → Validation → DB Update → History/Audit → Review UI**

---

## Deliverables

| Component | Path |
|-----------|------|
| Workflow helper | `public_html/includes/moghare360-contract-authorization-workflow-helper.php` |
| Workflow page | `public_html/erp-jobcard-contract-authorization-workflow.php` |
| Workflow submit | `public_html/submit-jobcard-contract-authorization-workflow.php` |
| Preview link update | `public_html/erp-jobcard-contract-authorization-preview.php` |

---

## Allowed Transitions

| From | To |
|------|-----|
| draft | pending_customer_approval |
| pending_customer_approval | approved, rejected, cancelled |
| approved | cancelled (reason required) |
| rejected | cancelled (reason required) |

---

## Boundaries

- Internal controlled workflow — NOT final legal e-signature
- No SQL / schema changes
- No public portal
- Cursor did not decide next roadmap step

---

**END OF SCOPE**
