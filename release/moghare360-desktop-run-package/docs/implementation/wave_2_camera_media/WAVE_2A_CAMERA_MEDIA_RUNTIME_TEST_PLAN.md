# WAVE 2A — Camera Media Runtime Test Plan

**Wave:** IMPLEMENTATION WAVE 2A  
**Date:** 2026-06-23

---

## CLI Test

**Command:** `C:\xampp\php\php.exe tools/test-wave-2a-camera-media-runtime.php`

Checks helper validation, file presence, no `type="file"`, no SQL, docs.

**Pass:** `WAVE 2A CAMERA MEDIA RUNTIME TEST PASSED`

---

## Browser Tests

| URL | Expected |
|-----|----------|
| `erp-jobcard-camera-capture.php` | Camera UI · no file input |
| `erp-jobcard-media-preview.php?jobcard_id=1` | Preview or empty state |
| Invalid `jobcard_id` | Controlled error |

---

**END OF TEST PLAN**
