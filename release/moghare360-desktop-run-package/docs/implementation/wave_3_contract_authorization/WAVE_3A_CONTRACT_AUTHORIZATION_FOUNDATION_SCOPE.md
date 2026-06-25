# WAVE 3A — Contract Authorization Foundation Scope

**Wave:** IMPLEMENTATION WAVE 3A  
**Parent:** IMPLEMENTATION WAVE 3 — Contract Authorization Runtime  
**Date:** 2026-06-23

---

## Objective

Runtime foundation for controlled JobCard contract and authorization records.

Flow: **JobCard → Contract/Authorization Type → Required Approval Context → Runtime Validation → DB Foundation Check → Safe Create/Block → Audit-ready Structure**

---

## Deliverables

| Component | Path |
|-----------|------|
| Helper | `public_html/includes/moghare360-contract-authorization-helper.php` |
| Form page | `public_html/erp-jobcard-contract-authorization.php` |
| Submit handler | `public_html/submit-jobcard-contract-authorization.php` |
| Preview page | `public_html/erp-jobcard-contract-authorization-preview.php` |
| SQL foundation (if needed) | `public_html/sql/wave_3a_contract_authorization_foundation.sql` |

---

## Schema Inspection Result

No safe existing JobCard-level authorization table with required columns was found in repo SQL/docs. SQL foundation file created for manual SSMS execution after ChatGPT approval.

---

## Boundaries

- Internal controlled authorization — **NOT** final legal e-signature
- Public portal **not** activated
- Payment / official accounting / SaaS **not** activated
- No auth/config/permission changes
- No customer/vehicle/jobcard v2 behavior changes
- Cursor did not decide next roadmap step

---

**END OF SCOPE**
