# WAVE 2A — Camera Media Runtime Scope

**Wave:** IMPLEMENTATION WAVE 2A — Camera-only Media Runtime Foundation  
**Parent:** IMPLEMENTATION WAVE 2 — Camera-only Media + Diagnostic Binding  
**Date:** 2026-06-23  
**Status:** IMPLEMENTED

---

## Objective

Runtime foundation for camera-only JobCard media capture:

**UI → Camera Capture → Validation → Local File Storage → Metadata Preview / Controlled Binding → Audit-ready Structure**

---

## Components

| File | Purpose |
|------|---------|
| `includes/moghare360-camera-media-helper.php` | Validation + local save APIs |
| `erp-jobcard-camera-capture.php` | Browser camera capture UI |
| `submit-jobcard-camera-capture.php` | POST handler · rejects `$_FILES` |
| `erp-jobcard-media-preview.php` | Local filesystem preview by `jobcard_id` |
| `storage/jobcard-media/.gitkeep` | Placeholder only |

---

## Locked Rules Enforced

| Rule | Wave 2A |
|------|---------|
| Camera direct only | ✅ `getUserMedia` + canvas |
| No upload bypass | ✅ No `type="file"` · `$_FILES` rejected |
| No external URL | ✅ Rejected in helper |
| Local storage only | ✅ `public_html/storage/jobcard-media/` |
| Controlled stages/types | ✅ Whitelist validation |

---

## Not Activated in Wave 2A

- Media metadata DB write
- Diagnostic PDF binding
- Public portal / domain media exposure

---

## Boundaries

- No SQL / schema / auth / config / permission changes
- Customer/Vehicle/JobCard v2 DB writes unchanged
- Not committed / not pushed
- **Cursor did not decide next roadmap step**

---

**END OF SCOPE**
