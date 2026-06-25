# WAVE 2C — Diagnostic File Binding Scope

**Wave:** IMPLEMENTATION WAVE 2C  
**Parent:** IMPLEMENTATION WAVE 2 — Camera-only Media + Diagnostic Binding  
**Date:** 2026-06-23

---

## Objective

Implement controlled Diagnostic File Binding foundation for JobCards using the verified `dbo.erp_jobcard_media` metadata foundation.

Flow: **JobCard → Diagnostic File Validation → Local Controlled Storage → Media Metadata DB Record → Media History/Audit**

---

## Deliverables

| Component | Path |
|-----------|------|
| Diagnostic helper | `public_html/includes/moghare360-diagnostic-file-helper.php` |
| Upload page | `public_html/erp-jobcard-diagnostic-file.php` |
| Submit handler | `public_html/submit-jobcard-diagnostic-file.php` |
| Preview page | `public_html/erp-jobcard-diagnostic-preview.php` |
| Local storage | `public_html/storage/jobcard-diagnostic/{jobcard_id}/` |

---

## Rules

| Rule | Status |
|------|--------|
| Camera-only photo capture unchanged | ✅ |
| Diagnostic file input allowed only on diagnostic page | ✅ |
| External URL rejected | ✅ |
| Unsafe extensions rejected | ✅ |
| Local storage only | ✅ |
| Metadata via `dbo.erp_jobcard_media` | ✅ |
| History via `dbo.erp_jobcard_media_history` | ✅ |
| `source` constraint locked to `CAMERA_ONLY` — semantic type in `media_stage` + `media_type` + `notes` | Documented |

---

## Boundaries

- No SQL file created / no schema change
- No auth/config/permission change
- No public portal / SaaS / accounting / payment gateway
- Cursor did not decide next roadmap step

---

**END OF SCOPE**
