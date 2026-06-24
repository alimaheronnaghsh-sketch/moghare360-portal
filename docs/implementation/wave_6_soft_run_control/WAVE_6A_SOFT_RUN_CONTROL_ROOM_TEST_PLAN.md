# WAVE 6A — Soft Run Control Room Test Plan

**Wave:** IMPLEMENTATION WAVE 6A  
**Date:** 2026-06-22

---

## CLI Test

```text
C:\xampp\php\php.exe tools/test-wave-6a-soft-run-control-room.php
```

Expected final line: `WAVE 6A SOFT RUN CONTROL ROOM TEST PASSED`

### Checks

- Helper and page exist
- Required APIs present
- Safe WAVE 2/3/4/5 closure references
- No INSERT/UPDATE/DELETE
- Page navigation links
- No file input · no POST · no final delivery
- Prior-wave helpers unchanged
- No auth/config changes
- Documentation exists

---

## Browser Test (after copy to htdocs)

| URL | Check |
|-----|-------|
| `http://localhost:8080/moghare360/erp-soft-run-control-room.php` | Loads · Soft Run status · wave panels |
| `http://localhost:8080/moghare360/erp-unified-operational-closure-dashboard.php` | WAVE 5 link works |
| `http://localhost:8080/moghare360/erp-jobcard-command-workbench.php` | Workbench link works |

### Manual Validation

- Soft Run status visible
- WAVE 2/3/4/5 statuses visible
- Runtime summary visible
- Operational links work
- No DB write · no POST · no delivery completion

---

**END OF TEST PLAN**
