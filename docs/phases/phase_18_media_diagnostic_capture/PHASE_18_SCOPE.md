# PHASE 18 — Media & Diagnostic Capture System — Scope

**Phase:** PHASE 18 — MEDIA AND DIAGNOSTIC CAPTURE SYSTEM  
**Status:** Documentation and planning only  
**SQL:** No SQL required

---

## Phase Objective

Design and lock the **Media and Diagnostic Capture System** for MOGHARE360 ERP before operational go-live.

---

## Locked Rules

| Rule | Status |
|------|--------|
| Database: **MOGHARE360_ERP** | LOCKED |
| Local laptop server = system of record | LOCKED |
| moghareh360.ir = Mirror Only | LOCKED |
| **UI → Validation Engine → Workflow Engine → Database → Audit Log** | LOCKED |
| **Camera direct only** | LOCKED |
| **No upload bypass** · **No free file upload** | LOCKED |
| Media bound to JobCard | LOCKED |
| Diagnostic PDFs bound to JobCard | LOCKED |
| Media and diagnostics auditable | LOCKED |
| **No media/diagnostic storage on domain** | LOCKED |
| **No cloud media storage** · **No host-side media storage** | LOCKED |
| No SaaS / portal / accounting / payment gateway | LOCKED |

---

## PHASE 18 Modules

1. Camera-only Input Photo Capture
2. 6 Input Photo Rule
3. 8 Output Photo Rule
4. JobCard Media Binding
5. Initial Diagnostic PDF
6. Secondary Diagnostic PDF
7. Final Diagnostic PDF
8. Media Audit / Immutability

---

## Allowed Scope

- `docs/phases/phase_18_media_diagnostic_capture/` (5 files)
- `docs/media/` (9 files)

---

## Forbidden Scope

- PHP runtime, SQL, schema, `public_html`, auth, config, release
- Modify existing forms; implement camera/diagnostic capture in runtime
- Upload UI, file input fields, free upload
- SaaS, portal, accounting, payment gateway activation
- Commit, push

---

## Phase 18 Constraints

- **PHASE 18 is documentation/planning only**
- **No runtime media implementation**
- **No PHP creation**
- **No SQL creation**
- **No database modification**
- **No existing form modification**
- **No upload UI**
- **No public portal activation**

---

**END OF SCOPE**
