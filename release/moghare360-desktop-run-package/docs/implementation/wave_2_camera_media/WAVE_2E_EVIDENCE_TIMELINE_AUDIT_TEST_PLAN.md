# WAVE 2E — Evidence Timeline Audit Test Plan

**Wave:** IMPLEMENTATION WAVE 2E  
**Date:** 2026-06-23

---

## CLI Test

**Command:** `C:\xampp\php\php.exe tools/test-wave-2e-evidence-timeline-audit.php`

**Expected:** `WAVE 2E EVIDENCE TIMELINE AUDIT TEST PASSED`

---

## Browser Test

| URL | Check |
|-----|-------|
| `/erp-jobcard-evidence-timeline.php?jobcard_id=1` | Timeline events, counts, warnings |
| `/erp-jobcard-evidence-timeline.php?jobcard_id=abc` | Invalid ID error |
| `/erp-jobcard-evidence-review.php?jobcard_id=1` | Timeline link present |

---

**END OF TEST PLAN**
