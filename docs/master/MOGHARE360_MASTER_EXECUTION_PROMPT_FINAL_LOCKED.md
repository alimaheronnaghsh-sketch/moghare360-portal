# MOGHARE360 — MASTER EXECUTION PROMPT FINAL LOCKED VERSION

**Document:** Official locked master execution prompt for MOGHARE360 ERP  
**Status:** FINAL LOCKED — documentation only  
**Encoding:** UTF-8  
**Scope:** Cursor / agent execution governance — no runtime change by this document alone

---

## 1. Project Identity

| Item | Value |
|------|-------|
| Product | MOGHARE360 ERP |
| Brand | Moghareh Motors / MOGHARE360 |
| Repo | `C:\Users\User\Documents\GitHub\alimaheronnaghsh-sketch\moghare360-portal` |
| Runtime | `C:\xampp\htdocs\moghare360` |
| Base URL | `http://localhost:8080/moghare360/` |
| Database | SQL Server — `moghare360_ERP` on `.\SQLEXPRESS` |

---

## 2. Phase Completion Baseline (Locked)

Phases **1 through 15** are completed, tested, committed, and pushed unless a mission doc explicitly states otherwise.

| Phase | Title | Status |
|-------|-------|--------|
| 1–10 | Core ERP through Commercial System | COMPLETED |
| 11 | Stabilization Sprint | COMPLETED |
| 12 | Soft Run Pilot | COMPLETED |
| 12.5 | Product Localization, Branding & Release Packaging | COMPLETED |
| 13 | Security & Access Hardening | COMPLETED |
| 14 | Production Deployment Plan | COMPLETED |
| 15 | Downloadable Release Package | COMPLETED (incl. .bak exclusion fix) |

**Product readiness (locked):**

- Internal Soft Run: READY
- Business Ready System: READY
- Commercial Demo Readiness: READY
- Local Release Candidate 1: READY
- Controlled Workshop Pilot: READY
- Persian / Branded / Asset-Controlled: READY
- Security / Access Audited: READY
- Deployment Planned: READY
- Release Packages (Demo + Local RC1): READY

**Not active (locked boundaries):**

- Production SaaS: NOT ACTIVE
- Public Customer Portal: NOT ACTIVE
- Official Accounting: NOT ACTIVE
- Payment Gateway / Billing / Tax Invoice: NOT ACTIVE

---

## 3. Architecture Flow (Locked)

All operational features must respect this chain:

**UI → Validation Engine → Workflow Engine → Database → Audit Log**

Rules:

- UI collects input and displays state only; no business rule bypass in UI alone.
- Validation Engine enforces field, boundary, and policy checks before write.
- Workflow Engine governs status transitions and controlled write routes.
- Database is the system of record; migrations must be idempotent unless explicitly approved.
- Audit Log records significant actions where module design requires history.

No agent may skip validation or workflow layers to write directly without documented exception in mission scope.

---

## 4. Media Rule (Locked)

Media capture for product features must follow:

- **Camera direct only**
- **No upload bypass**

Gallery upload, file-picker bypass, or pre-stored image injection as a substitute for live camera capture is prohibited unless a future mission explicitly unlocks it with owner approval.

---

## 5. Absolute Forbidden Changes (Locked)

Unless a mission explicitly grants read-only inspection or owner-approved exception:

| Forbidden | Reason |
|-----------|--------|
| `staff-auth.php` | Production login boundary |
| `access-control.php` | Auth boundary |
| `staff-login.php` | Login boundary |
| `config.php` | Config boundary |
| `config.example.php` | Config boundary |
| `private/erp-config.php` | Secrets boundary |
| `private/erp-config.example.php` | Private config boundary |
| Legacy customer portal files | Legacy boundary |
| Codex ZIP archive | Archive boundary |

Also forbidden without explicit mission scope:

- Production login rewrite
- Auth architecture rewrite
- Permission model rewrite
- Destructive DB migration (DROP, rename, destructive ALTER)
- Legacy portal change
- Customer public portal activation
- Real SaaS / tenant production activation
- Payment gateway, billing engine, official accounting, tax invoice
- External SMS / WhatsApp / Email / BI integration
- Dependency installation without approval
- Credentials or secrets in repo
- Real customer data export in packages
- Production installer or auto deploy that overwrites real config

---

## 6. Execution Principles (Locked)

1. **Mission scope only** — build what the active PHASE / MASTER mission defines; no scope creep.
2. **Read-only by default** for audit, report, plan, and packaging-inspection layers.
3. **No SQL unless required** — prefer static/read-only layers; if SQL is needed: idempotent, no DROP, no rename, no destructive ALTER.
4. **Safe integration** — small links on non-sensitive product pages only; no login/auth rewrite.
5. **Persian RTL UI** for product-facing pages unless mission says otherwise.
6. **Font stack (CSS only):** `Vazirmatn, Tahoma, Segoe UI, Arial, sans-serif` — no unlicensed font files in repo.
7. **Brand rule:** Moghareh Motors logo only for Moghareh / MOGHARE360; no car brand logos without license.
8. **Asset registry** required before commercial use of new visual assets.
9. **Commit / push** only when user explicitly requests.
10. **Test tool** per phase where defined; mission signoff docs updated only when mission specifies.

---

## 7. Standard Paths (Locked)

```
Repo:     C:\Users\User\Documents\GitHub\alimaheronnaghsh-sketch\moghare360-portal
Runtime:  C:\xampp\htdocs\moghare360
URL:      http://localhost:8080/moghare360/
SQL:      public_html/sql/sqlserver/
Tools:    tools/test-phase-*.php
Missions: docs/missions/phase_*/*
Product:  docs/product/
Release:  docs/release/
Deploy:   docs/deployment/
Master:   docs/master/
Packages: release/moghare360-demo-package.zip
          release/moghare360-local-rc1.zip
```

Sync rule (local):

```powershell
robocopy "<repo>\public_html" "C:\xampp\htdocs\moghare360" /E /XD ".git" /XF "*.md"
```

---

## 8. Packaging Boundaries (Locked — Phase 15)

Packages are **Demo** and **Local RC1** only — not production installers.

Excluded from all packages:

- `private/`, credentials, secrets
- `config.php`, `config.example.php`, `erp-config.php`, `erp-config.example.php`
- `.git/`, `logs/`, `backups/`, `uploads/` (real data)
- Any filename containing `.bak`, plus `.log`, `.tmp`, nested source `.zip`
- `public_html/release/` artifact zips inside local package

ZIP must pass content inspection (`tar -tf` in script; test tool entry scan) before PASS.

---

## 9. Security Audit Baseline (Locked — Phase 13)

- Write routes: auth + permission + CSRF expected
- Pilot CSRF: self-contained in `moghare360-pilot-helper.php` — root fix PASSED
- Role matrix: design-only; does not change live permissions
- Sensitive boundary report: status only; never display private file contents

---

## 10. Localization & Brand Baseline (Locked — Phase 12.5)

- Primary UI language: Persian managerial
- Controlled English technical terms allowed with Persian explanation
- Official dictionary in `moghare360-localization-helper.php`
- Logo path: `public_html/assets/moghare360-brand/moghareh-motors-logo.jpg` (owner-provided) or text fallback

---

## 11. Deployment Plan Baseline (Locked — Phase 14)

- Planning only — **no production deploy execution** by agent
- Environment / backup / migration / rollback / monitoring docs in `docs/deployment/`
- Production execution: **NO** until owner approval

---

## 12. Mission Execution Workflow (Locked)

For each new PHASE or MASTER mission:

1. Read mission scope and forbidden list.
2. Update prior phase signoff docs **only if** mission PART 0 requires it.
3. Implement allowed files only.
4. Create or update test tool if mission requires.
5. Create or update mission docs under `docs/missions/`.
6. Run `php -l` on new PHP files.
7. Run phase test tool; report RESULT.
8. Provide browser URLs if applicable.
9. Output in mission-defined report format.
10. Status: **PENDING USER TEST** unless user completes test and requests commit.

---

## 13. Standard Test Commands (Locked)

```powershell
cd "C:\Users\User\Documents\GitHub\alimaheronnaghsh-sketch\moghare360-portal"

# Phase 15 example (after packaging scripts)
C:\xampp\php\php.exe "tools\test-phase-15-release-package.php"
powershell -ExecutionPolicy Bypass -File "tools\package-moghare360-local-release.ps1"
powershell -ExecutionPolicy Bypass -File "tools\package-moghare360-demo.ps1"
```

Do **not** paste markdown fences into PowerShell — run commands only.

---

## 14. Standard Cursor Report Format (Locked)

```
PHASE N — <TITLE> BUILD RESULT

Built Files:
* ...

SQL:
* Not required / or <path> if created

Mission Docs:
* ...

Integration:
* updated / unchanged

Tests To Run:
* ...

Forbidden Files:
* Confirm untouched / skipped if not present

Status:
PENDING USER TEST
```

---

## 15. Master Document Lock Statement

This file is the **FINAL LOCKED** master execution prompt for MOGHARE360 ERP.

- Changes require explicit owner unlock of MASTER mission scope.
- This document does not, by itself, modify runtime, database, auth, packages, or portal behavior.
- Agents must treat conflicts between ad-hoc instructions and this document as: **narrower mission scope wins for implementation; this document wins for boundaries and forbidden scope.**

---

**END OF LOCKED MASTER EXECUTION PROMPT**
