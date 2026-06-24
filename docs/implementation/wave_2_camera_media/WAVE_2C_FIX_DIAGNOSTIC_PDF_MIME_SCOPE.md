# WAVE 2C-FIX — Diagnostic PDF MIME Scope

**Wave:** WAVE 2C-FIX — Diagnostic PDF MIME Constraint SQL Patch  
**Parent:** IMPLEMENTATION WAVE 2C — Diagnostic File Binding  
**Date:** 2026-06-23  
**Database:** MOGHARE360_ERP

---

## Objective

Prepare a safe SQL patch that extends `dbo.erp_jobcard_media.mime_type` CHECK constraint to allow `application/pdf` for controlled diagnostic PDF metadata binding.

---

## Deliverable

| Item | Path |
|------|------|
| SQL patch | `public_html/sql/wave_2c_fix_diagnostic_pdf_mime_constraint.sql` |
| Static CLI test | `tools/test-wave-2c-fix-diagnostic-pdf-mime.php` |

---

## Constraint Change

| Before (Wave 2B-FIX) | After (Wave 2C-FIX) |
|----------------------|---------------------|
| `image/jpeg`, `image/png`, `image/webp` | `application/pdf`, `image/jpeg`, `image/png`, `image/webp` |

---

## Boundaries

- SQL prepared only — **not executed by Cursor**
- User must execute manually in SSMS
- Runtime PHP not modified
- Camera-only photo rule unchanged
- Diagnostic file input remains controlled on diagnostic page only
- No upload bypass / no external URL / no public portal
- Cursor did not decide next roadmap step

---

**END OF SCOPE**
