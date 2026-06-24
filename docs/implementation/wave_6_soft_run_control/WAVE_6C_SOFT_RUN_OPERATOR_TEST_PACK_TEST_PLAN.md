# WAVE 6C — Soft Run Operator Test Pack Test Plan

**Wave:** IMPLEMENTATION WAVE 6C  
**Date:** 2026-06-22

---

## CLI Test

```text
C:\xampp\php\php.exe tools/test-wave-6c-soft-run-operator-test-pack.php
```

Expected: `WAVE 6C SOFT RUN OPERATOR TEST PACK TEST PASSED`

---

## Browser Test

| URL | Check |
|-----|-------|
| `http://localhost:8080/moghare360/erp-soft-run-operator-test-pack.php` | Loads · TEST_PACK_READY · 20 steps |
| `http://localhost:8080/moghare360/erp-soft-run-scenario-board.php` | Links to test pack |
| `http://localhost:8080/moghare360/erp-soft-run-control-room.php` | Links to test pack |

---

**END OF TEST PLAN**
