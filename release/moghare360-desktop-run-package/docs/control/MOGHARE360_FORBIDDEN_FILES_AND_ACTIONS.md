# MOGHARE360 — Forbidden Files and Actions

**Status:** Active — applies to all phases unless explicitly overridden in phase ALLOWED SCOPE

---

## Forbidden Files (Do Not Modify Without Explicit Mission)

| File / Path | Reason |
|-------------|--------|
| `staff-auth.php` | Production login |
| `access-control.php` | Permission model |
| `staff-login.php` | Production login |
| `config.php` | Runtime config |
| `config.example.php` | Config template |
| `private/erp-config.php` | Secret values — local only |
| Legacy customer portal paths | Public portal boundary |

---

## Forbidden Actions

| Action | Status |
|--------|--------|
| Modify auth architecture | Forbidden |
| Modify permission model | Forbidden |
| Modify private config values | Forbidden |
| Modify database schema without phased SQL + User SSMS | Forbidden |
| Create executable SQL in unauthorized phases | Forbidden |
| Create/modify PHP runtime in `public_html` without phase scope | Forbidden |
| Modify release packages without Phase 15 scope | Forbidden |
| Commit real secrets | Forbidden |
| Push without ChatGPT approval | Forbidden |
| Auto deployment to production | Forbidden |
| Activate public customer portal | Forbidden — No public customer portal activation |
| Activate production SaaS | Forbidden — No production SaaS activation |
| Activate official accounting | Forbidden — No official accounting activation |
| Create payment/tax/billing integration | Forbidden — No payment gateway/billing/tax integration created |

---

## Forbidden Data Practices

- No cloud storage for business data
- No business data on mirror domain `moghareh360.ir`
- No direct database write from UI (bypassing Validation/Workflow engines)
- **No upload bypass** for media — **Camera direct only**

---

## Allowed Exceptions

Only when a phase prompt explicitly lists a path in **ALLOWED SCOPE** and does not list it in **FORBIDDEN SCOPE**.

---

## Architecture (Required for All Writes)

**UI → Validation Engine → Workflow Engine → Database → Audit Log**

---

**END OF FORBIDDEN LIST**
