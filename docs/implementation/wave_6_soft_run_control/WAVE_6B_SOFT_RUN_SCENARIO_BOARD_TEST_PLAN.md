# WAVE 6B — Soft Run Scenario Board Test Plan

**Wave:** IMPLEMENTATION WAVE 6B  
**Date:** 2026-06-22

---

## CLI Test

```text
C:\xampp\php\php.exe tools/test-wave-6b-soft-run-scenario-board.php
```

Expected final line: `WAVE 6B SOFT RUN SCENARIO BOARD TEST PASSED`

---

## Browser Test (after copy to htdocs)

| URL | Check |
|-----|-------|
| `http://localhost:8080/moghare360/erp-soft-run-scenario-board.php` | Loads · pilot status · scenarios · pages |
| `http://localhost:8080/moghare360/erp-soft-run-control-room.php` | Links to scenario board |

---

**END OF TEST PLAN**
