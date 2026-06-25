# MOGHARE360 — Read-Only Test and Signoff Plan

**Status:** Test planning only — **No actual runtime tests in PHASE 09** (PHP pages not created)

---

## Why No Runtime Tests Yet

PHASE 09 produces specifications only. Tests execute in **PHASE 10** after PHP implementation.

---

## Future Test Groups (Phase 10)

### File Existence Test

| Test ID | Check |
|---------|-------|
| T-FE-001 | All 8 `erp-readonly-*.php` exist in `public_html/` |
| T-FE-002 | Read-only helper(s) exist in `includes/` |
| T-FE-003 | CSS `moghare360-readonly-visibility.css` exists |

**Tool (planned):** `tools/test-phase-10-readonly-visibility.php`

### Forbidden File Change Test

| Test ID | Check |
|---------|-------|
| T-FF-001 | `staff-auth.php` unchanged |
| T-FF-002 | `access-control.php` unchanged |
| T-FF-003 | `private/erp-config.php` not in package paths |

### Browser Route Test

| Test ID | Check |
|---------|-------|
| T-BR-001 | Each of 8 routes returns HTTP 200 when authenticated |
| T-BR-002 | Unauthenticated request redirects to login |

**Tool:** PowerShell test planning + manual browser checklist

### Read-Only Behavior Test

| Test ID | Check |
|---------|-------|
| T-RO-001 | No page contains `<form method="post">` for data mutation |
| T-RO-002 | No INSERT/UPDATE/DELETE in page source helpers |

### No Form Submit Test

| Test ID | Check |
|---------|-------|
| T-NS-001 | Grep pages for submit buttons — none for write |

### No SQL Write Test

| Test ID | Check |
|---------|-------|
| T-SQL-001 | Helpers contain only SELECT or static data |
| T-SQL-002 | No EXEC, INSERT, UPDATE, DELETE strings |

### No Workflow Mutation Test

| Test ID | Check |
|---------|-------|
| T-WF-001 | No workflow transition POST endpoints on read-only pages |

### No Public Portal Activation Test

| Test ID | Check |
|---------|-------|
| T-PP-001 | Pages require staff session |
| T-PP-002 | No customer-facing routes |

### Permission Guard Smoke Test

| Test ID | Check |
|---------|-------|
| T-PG-001 | Low-permission user denied on database risk board |
| T-PG-002 | Owner can access all 8 pages |

### Source Document Consistency Test

| Test ID | Check |
|---------|-------|
| T-SD-001 | Domain count = 12 on overview |
| T-SD-002 | Risk metrics match docs (46 empty, 10 dual IDs) |

---

## PowerShell Test Planning (Phase 10)

```powershell
# Planned — not executed in Phase 09
php tools/test-phase-10-readonly-visibility.php
```

---

## Browser Test Planning (Phase 10)

1. Login as platform owner
2. Visit each route from local route plan
3. Verify read-only banners and no write controls
4. Logout — verify redirect on revisit

---

## Signoff Criteria (Phase 10)

| Criterion | Required |
|-----------|----------|
| All T-* tests pass | Yes |
| Phase 09 specs unchanged or versioned | Yes |
| No forbidden file modifications | Yes |
| Product boundary banners visible | Yes |
| ChatGPT approval for Phase 10 complete | Yes |

---

**END OF READ-ONLY TEST AND SIGNOFF PLAN**
