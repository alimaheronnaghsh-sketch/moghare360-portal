# WAVE 4B — Delivery Eligibility Test Plan

**Wave:** IMPLEMENTATION WAVE 4B  
**Date:** 2026-06-22

---

## CLI Test

Run:

```
C:\xampp\php\php.exe tools/test-wave-4b-delivery-eligibility.php
```

Expected: `WAVE 4B DELIVERY ELIGIBILITY TEST PASSED`

Checks: helper/page existence, required APIs, WAVE 4A integration, no DB writes, page links, no POST/file input, no delivery action, prior wave helpers unchanged, docs exist.

---

## Browser Test

After copying `public_html` changes to htdocs:

| URL | Expected |
|-----|----------|
| `erp-jobcard-delivery-eligibility.php?jobcard_id=1` | Page loads; status from real data; gate statuses visible |
| `erp-jobcard-delivery-eligibility.php?jobcard_id=abc` | Controlled invalid ID error |
| `erp-jobcard-final-readiness.php?jobcard_id=1` | Link «بررسی صلاحیت تحویل» present |

Verify: no POST form, no final delivery action, no delivery record creation, links work.

---

## Manual Validation

- ELIGIBLE / REVIEW_REQUIRED / NOT_ELIGIBLE / EMPTY / ERROR appear per data
- Final readiness, evidence gate, authorization gate statuses visible
- Read-only — no DB write

---

**END OF TEST PLAN**
