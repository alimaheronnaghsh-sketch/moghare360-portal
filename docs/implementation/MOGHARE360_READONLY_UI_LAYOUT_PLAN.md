# MOGHARE360 — Read-Only UI Layout Plan

**Status:** Planning only — No frontend files in Phase 09

---

## UI Layout Principles

| Principle | Rule |
|-----------|------|
| Simple admin readable pages | RTL Persian; clear headings |
| **No forms that submit data** | Display only |
| **No write buttons** | No save/submit |
| **No approve/apply buttons** | Workflow display only |
| **No upload buttons** | **No media upload UI** |
| No public portal styling | Staff ERP industrial brand (Phase 12.5 CSS when implemented) |
| Clear status badges | FOUNDATION_REFERENCE, PREVIEW_ONLY, NOT_PRODUCTION_READY |
| Architecture cards | 12-domain grid on overview |
| Matrix tables | Validation, workflow, permission gates |
| Risk panels | Database risk board |

---

## Visual Sections (All Pages)

```
┌─────────────────────────────────────────┐
│ PAGE HEADER (title + breadcrumb)        │
├─────────────────────────────────────────┤
│ SCOPE BANNER (read-only / preview-only) │
├─────────────────────────────────────────┤
│ SOURCE DOCUMENT LIST (links to docs/)   │
├─────────────────────────────────────────┤
│ MATRIX / TABLE AREA (main content)      │
├─────────────────────────────────────────┤
│ RISK / DECISION AREA (warnings)         │
├─────────────────────────────────────────┤
│ BOUNDARY WARNING AREA (product limits)  │
└─────────────────────────────────────────┘
```

---

## Page-Specific Layout Notes

| Page | Matrix/table area | Risk/decision area |
|------|-------------------|-------------------|
| Architecture overview | 12 domain cards + flow | SaaS/accounting/portal warnings |
| Domain map | 96-row grouped table | Ambiguous ownership callouts |
| Validation matrix | 12-group rule tables | Media rules panel |
| Workflow contract | Allowed + forbidden tables | State definitions |
| Permission gates | Gate matrix | No permission model change note |
| Audit contract | Field + action tables | Skip-audit forbidden |
| Module readiness | Readiness badge grid | NOT_PRODUCTION_READY banner |
| Database risk board | Metric cards | Do not create SQL yet |

---

## Boundary Warning Area (Standard Text)

- **No production SaaS activation**
- **No public customer portal activation**
- **No official accounting activation**
- **No payment gateway/billing/tax integration created**
- **Controlled writes are NOT approved yet**

---

## Media Policy Display

On validation matrix and architecture overview:

- **Camera direct only**
- **No upload bypass**

No camera/upload UI on read-only pages.

---

## CSS (Phase 10)

- Reuse `moghare360-brand-localization.css` / security hardening patterns
- New: `moghare360-readonly-visibility.css` (planned, not created in Phase 09)

---

**END OF READ-ONLY UI LAYOUT PLAN**
