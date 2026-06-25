# WAVE 5A — Unified JobCard Command Test Plan

**Wave:** IMPLEMENTATION WAVE 5A  
**Date:** 2026-06-22

---

## CLI Test

```
C:\xampp\php\php.exe tools/test-wave-5a-unified-jobcard-command-center.php
```

Expected: `WAVE 5A UNIFIED JOBCARD COMMAND CENTER TEST PASSED`

---

## Browser Test

| URL | Expected |
|-----|----------|
| `erp-jobcard-command-center.php?jobcard_id=1` | Unified status + all layer statuses |
| `erp-jobcard-command-center.php?jobcard_id=abc` | Controlled invalid ID error |

---

**END OF TEST PLAN**
