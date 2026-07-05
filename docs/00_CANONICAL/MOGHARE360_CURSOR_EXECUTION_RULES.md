# MOGHARE360 — Cursor Execution Rules

**Document ID:** CANONICAL-005  
**Status:** EXECUTION AUTHORITY — Binding on all Cursor agents  
**Applies to:** All MOGHARE360 implementation tasks in this repository

---

## 1. Role Separation

| Role | Responsibility |
|------|----------------|
| **Owner** | Final scope, signoff, commit, push, production |
| **ChatGPT / Architect** | Architecture, scope, sequence, phase definitions |
| **Cursor** | Implementer only — executes approved phases |

Cursor must **not** invent product scope, reorder critical path, or declare completion.

---

## 2. Mandatory Read Order

Before **any** code change, read:

1. `docs/00_CANONICAL/MOGHARE360_MASTER_PRODUCT_BLUEPRINT.md`
2. `docs/00_CANONICAL/MOGHARE360_EXECUTION_ROADMAP.md`
3. `docs/00_CANONICAL/MOGHARE360_CURSOR_EXECUTION_RULES.md` (this file)
4. Phase-specific scope report (if exists)
5. `docs/00_CANONICAL/MOGHARE360_DATABASE_GAP_MATRIX.md` (if DB touched)
6. `docs/00_CANONICAL/MOGHARE360_RUNTIME_CLEANUP_PLAN.md` (if runtime touched)

On conflict: **Master Blueprint** wins for product intent; **phase scope** wins for file list.

---

## 3. Scope Rules

| Rule | Detail |
|------|--------|
| **No new scope** | Without owner approval |
| **No feature creep** | Stay inside phase Included list |
| **No C-2D / JobCard conversion** | Until owner explicitly unlocks |
| **No P12** | Out of program |
| **No production-ready claim** | Until browser UAT + owner signoff |

---

## 4. Forbidden Changes (Global Freeze)

Unless phase document **explicitly allows**:

| Area | Forbidden files / actions |
|------|---------------------------|
| OTP | `m360-otp-helper.php`, `m360-otp-config-loader.php`, OTP UI, root OTP routes |
| Auth/Login | `staff-auth.php`, `access-control.php`, login flows |
| DB schema | Any `.sql` migration, ALTER, CREATE without full proposal + approval |
| Private config | `private/m360-otp-config.php`, secrets |
| Physical delete | Any runtime file removal |
| Commit / push | All — owner only |
| Rename / move runtime | Forbidden in reset phase |

---

## 5. Completion Rules

| Rule | Detail |
|------|--------|
| **No fixture-only completion** | CLI tests supplement browser UAT |
| **Browser UAT required** | Real click path on XAMPP/staging |
| **SQL truth required** | Data in intended tables; payload hacks documented |
| **No secret in repo** | Private config gitignored |
| **Declare commit eligibility** | Every phase report ends with status |

---

## 6. SQL Rules

1. Gap matrix updated before SQL proposal.
2. Full proposal: tables, columns, types, FKs, indexes, idempotency, rollback.
3. Owner written approval.
4. No destructive DDL without explicit owner command.
5. Do not run SQL unless phase allows execution.

---

## 7. Testing Rules

| Test type | Role |
|-----------|------|
| Fixture CLI (`tools/test-*.php`) | Regression guard — **not** completion proof |
| Diagnostic (`tools/diagnose-*.php`) | Investigation — report findings |
| Browser UAT | **Required** for phase completion |
| XAMPP copy | Deploy changed files to `C:\xampp\htdocs\moghare360\` when phase requires live test |

---

## 8. Documentation Rules

| Action | Rule |
|--------|------|
| Canonical docs | Only owner-approved updates to `docs/00_CANONICAL/` |
| Phase reports | Create scope + final report per phase in `docs/audit/` |
| Do not create unsolicited docs | User must request or phase must require |

---

## 9. Phase Document Required Sections

Every phase **must** list:

### 9.1 Included
What this phase delivers.

### 9.2 Excluded
What is explicitly out of scope.

### 9.3 Files allowed to change
Exact path list.

### 9.4 Files forbidden
OTP, Auth, schema, etc.

### 9.5 Tests
CLI tests to run.

### 9.6 Browser UAT
Checklist with pass/fail criteria.

### 9.7 Commit eligibility
One of:
- `NOT_ELIGIBLE_*` (with reason)
- `ELIGIBLE_AFTER_BROWSER_UAT`
- `ELIGIBLE_AFTER_OWNER_SIGNOFF`

---

## 10. Working Tree Discipline

- ~114 open files — **do not commit** until owner split approved
- Do not widen scope to "clean up" unrelated files
- Modified OTP files while frozen = violation — report, do not silently continue

---

## 11. UI/UX Rules (from Master Blueprint)

- Persian RTL default
- Luxury UI (`moghare360-v1-luxury-ui.css`)
- Minimum clicks
- Camera-direct photos (no upload bypass unless approved)
- CSRF on staff POST
- Token hash only for customer links

---

## 12. Vehicle Scope Rule

Only owner-approved brands: Toyota, Lexus, Kia, Hyundai, BYD, Lucano, Chery.

No new brand/model in code or seed without owner approval.

---

## 13. Customer Cartable Rule

- UI label: **کارتابل مشتری** (not "public link")
- Hybrid payload bridge is interim — DB table required for production
- Raw token never in payload/DB

---

## 14. Escalation

Stop and report to owner when:

- Browser contradicts fixture PASS
- OTP/auth regression detected
- Scope ambiguity
- DB change needed but not approved
- Secret exposure risk

---

## 15. Quick Reference — Current Phase

| Phase | Status |
|-------|--------|
| PR-00 Program Reset | **ACTIVE** — docs only |
| OTP recovery | **NEXT** — after owner approves |
| Intake browser UAT | Blocked on OTP |
| C-2D JobCard | **FORBIDDEN** |
| Commit | **FORBIDDEN** |

---

**END OF CURSOR EXECUTION RULES**
