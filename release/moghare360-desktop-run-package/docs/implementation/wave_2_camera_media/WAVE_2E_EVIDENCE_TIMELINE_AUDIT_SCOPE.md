# WAVE 2E — Evidence Timeline Audit Scope

**Wave:** IMPLEMENTATION WAVE 2E  
**Parent:** IMPLEMENTATION WAVE 2 — Camera-only Media + Diagnostic Binding  
**Date:** 2026-06-23

---

## Objective

Read-only Evidence Timeline and Audit Review layer for JobCard media and diagnostic evidence.

Flow: **JobCard → Media Metadata → Media History → Timeline View → Audit Review UI**

---

## Deliverables

| Component | Path |
|-----------|------|
| Timeline helper | `public_html/includes/moghare360-jobcard-evidence-timeline-helper.php` |
| Timeline page | `public_html/erp-jobcard-evidence-timeline.php` |
| Navigation link | `erp-jobcard-evidence-review.php` → timeline |

---

## Boundaries

- Read-only — no DB write
- Uses `dbo.erp_jobcard_media` + `dbo.erp_jobcard_media_history`
- `relative_path` only — no public `file_path` URLs
- Orphan history rows shown as audit warnings
- Camera/diagnostic/gate behavior unchanged
- Cursor did not decide next roadmap step

---

**END OF SCOPE**
