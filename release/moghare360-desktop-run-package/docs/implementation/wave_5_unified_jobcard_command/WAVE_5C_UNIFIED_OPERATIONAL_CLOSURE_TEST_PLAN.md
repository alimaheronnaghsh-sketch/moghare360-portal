# WAVE 5C — Unified Operational Closure Test Plan

**Wave:** IMPLEMENTATION WAVE 5C  
**Date:** 2026-06-22

---

## CLI Test

```text
C:\xampp\php\php.exe tools/test-wave-5c-unified-operational-closure.php
```

Expected final line: `WAVE 5C UNIFIED OPERATIONAL CLOSURE TEST PASSED`

### Checks

- Closure helper and dashboard exist
- Required helper APIs present
- Helper reads `dbo.erp_jobcards` with `jobcard_id` / `jobcard_number`
- No forbidden fields (`jobcard_code`, `is_active`, `is_deleted`, `tenant_id`, `company_id`)
- Safe WAVE 5A / 5B references
- No INSERT/UPDATE/DELETE
- Dashboard navigation links (workbench, command center, WAVE 2/3/4 closure, readiness, eligibility, clearance preview)
- No file input · no POST form · no final delivery action
- Prior-wave helpers unchanged
- No auth/config changes
- Documentation exists

---

## Browser Test (after copy to htdocs)

| URL | Check |
|-----|-------|
| `http://localhost:8080/moghare360/erp-unified-operational-closure-dashboard.php` | Loads · closure status · counts · recent rows |
| `http://localhost:8080/moghare360/erp-jobcard-command-workbench.php` | Workbench link works |
| `http://localhost:8080/moghare360/erp-jobcard-command-center.php?jobcard_id=1` | Command center link works |

### Manual Validation

- WAVE 5 closure status READY/PARTIAL/EMPTY/ERROR based on data
- Total JobCards and unified status counts visible
- Sample command status visible
- WAVE 2/3/4 closure links work
- No DB write · no POST · no delivery completion

---

**END OF TEST PLAN**
