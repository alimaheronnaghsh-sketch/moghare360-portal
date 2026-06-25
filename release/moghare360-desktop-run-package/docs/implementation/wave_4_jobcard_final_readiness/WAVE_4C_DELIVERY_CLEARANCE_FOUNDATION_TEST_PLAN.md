# WAVE 4C — Delivery Clearance Foundation Test Plan

**Wave:** IMPLEMENTATION WAVE 4C  
**Date:** 2026-06-22

---

## CLI Test

```
C:\xampp\php\php.exe tools/test-wave-4c-delivery-clearance-foundation.php
```

Expected: `WAVE 4C DELIVERY CLEARANCE FOUNDATION TEST PASSED`

---

## Browser Test

After copying `public_html` to htdocs:

| URL | Expected |
|-----|----------|
| `erp-jobcard-delivery-clearance.php?jobcard_id=1` | Create page loads; eligibility visible; schema blocked or ready |
| `erp-jobcard-delivery-clearance-preview.php?jobcard_id=1` | Preview loads read-only |
| `erp-jobcard-delivery-eligibility.php?jobcard_id=1` | Clearance navigation links present |

---

## Manual Validation

- Schema missing → BLOCKED warning
- After SSMS SQL → valid clearance creates record + history
- Invalid payload rejected
- NOT_ELIGIBLE cannot use `cleared` status
- No final delivery / completion / payment / e-signature

---

**END OF TEST PLAN**
