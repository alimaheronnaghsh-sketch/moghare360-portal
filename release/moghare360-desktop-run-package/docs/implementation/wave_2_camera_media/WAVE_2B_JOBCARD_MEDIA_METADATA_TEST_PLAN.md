# WAVE 2B — JobCard Media Metadata Test Plan

**Wave:** IMPLEMENTATION WAVE 2B  
**Date:** 2026-06-23

---

## CLI Test

**Command:** `C:\xampp\php\php.exe tools/test-wave-2b-jobcard-media-metadata.php`

### Required Checks

| # | Check |
|---|-------|
| 1 | Metadata helper exists |
| 2 | Camera helper exists |
| 3 | Capture submit exists |
| 4 | Submit rejects file upload bypass |
| 5 | Submit saves media before metadata bind |
| 6 | Submit does not fake metadata success when schema blocked |
| 7 | Prepared statement usage in metadata helper |
| 8 | Preview path traversal safe |
| 9 | No `input type="file"` on capture/submit |
| 10 | No SQL files created |
| 11 | Docs exist |
| 12 | Status marker: `DB_METADATA_WRITE_BLOCKED_SAFE_MEDIA_SCHEMA_NOT_CONFIRMED` OR activated |

**Expected:** `WAVE 2B JOBCARD MEDIA METADATA TEST PASSED`

---

## Browser Test

Copy `public_html/` changes to `C:\xampp\htdocs\moghare360\` then:

| URL | Check |
|-----|-------|
| `/erp-jobcard-camera-capture.php` | Loads, no file input, camera API |
| `/submit-jobcard-camera-capture.php` (POST) | Local save + blocked metadata message OR metadata success |
| `/erp-jobcard-media-preview.php?jobcard_id=1` | Local thumbnails + metadata pending message |
| `/erp-jobcard-media-preview.php?jobcard_id=abc` | Controlled invalid ID error |

---

## Manual Validation

- Camera capture saves file locally
- Metadata DB write succeeds **OR** controlled blocked message appears
- Upload/file input does not exist
- Upload bypass attempt rejected
- Media preview still works
- Invalid `jobcard_id` shows controlled error

---

**END OF TEST PLAN**
