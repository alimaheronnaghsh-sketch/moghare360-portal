# WAVE 2D — JobCard Evidence Gate Scope

**Wave:** IMPLEMENTATION WAVE 2D  
**Parent:** IMPLEMENTATION WAVE 2 — Camera-only Media + Diagnostic Binding  
**Date:** 2026-06-23

---

## Objective

Implement read-only JobCard Evidence Review and Completeness Gate using existing `dbo.erp_jobcard_media` and `dbo.erp_jobcard_media_history`.

Flow: **JobCard → Media Metadata Read → Diagnostic Metadata Read → Evidence Completeness Rules → Review UI → Controlled Gate Result**

---

## Deliverables

| Component | Path |
|-----------|------|
| Evidence gate helper | `public_html/includes/moghare360-jobcard-evidence-gate-helper.php` |
| Review page | `public_html/erp-jobcard-evidence-review.php` |
| CLI test | `tools/test-wave-2d-jobcard-evidence-gate.php` |

---

## Required Evidence (Minimum)

| # | Rule |
|---|------|
| 1 | `input` + (`front` OR `other`) |
| 2 | `input` + (`odometer` OR `dashboard`) |
| 3 | `output` + (`front` OR `other`) |
| 4 | `media_type=diagnostic` + stage in `diagnostic_initial`, `diagnostic_secondary`, `diagnostic_final` |

## Statuses

| Status | Meaning |
|--------|---------|
| COMPLETE | All minimum evidence present |
| PARTIAL | Some evidence, required set incomplete |
| EMPTY | No media for JobCard |
| ERROR | Invalid ID or DB read failure |

---

## Boundaries

- Read-only — no DB write
- Camera/diagnostic capture unchanged
- No SQL / schema / auth / config / permission changes
- Cursor did not decide next roadmap step

---

**END OF SCOPE**
