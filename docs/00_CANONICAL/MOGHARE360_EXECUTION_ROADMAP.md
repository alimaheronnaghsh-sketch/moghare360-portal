# MOGHARE360 — Execution Roadmap

**Document ID:** CANONICAL-004  
**Status:** EXECUTION AUTHORITY — Time-phased delivery plan  
**Baseline date:** Program Reset PR-00  
**Target:** Complete Persian RTL auto-workshop ERP (not demo/RC)

---

## Roadmap Overview

```
48h     → Stabilization + canonical lock + OTP isolate
2 weeks → Executable intake (online → cartable → lock)
1 month → Operational core (JobCard → QC → delivery)
3 months→ ERP foundation (inventory, purchase, finance base, HR)
6 months→ Sellable product (accounting, logistics, UAT package)
9 months→ Hardened product (multi-company, devices, security)
```

---

## Milestone 0 — 48-Hour Stabilization Target

### Deliverables
- [x] Canonical control pack (this folder + PR-00 report)
- [ ] OTP regression isolated and root cause documented with browser proof
- [ ] Runtime file classification locked (`RUNTIME_CLEANUP_PLAN.md`)
- [ ] Working tree inventory — no new scope files

### DB deliverables
- None (read-only gap matrix only)

### Runtime deliverables
- OTP browser diagnosis only (fix in dedicated OTP phase if approved)
- No intake/cartable feature additions

### Browser UAT
- Reproduce OTP failure on `customer-request.php`
- Document exact error for 09128166648 or test mobile

### Owner signoff
- Approve canonical docs as execution authority
- Approve OTP-first sequence

### Commit rule
**NOT_ELIGIBLE** — program reset only

---

## Milestone 1 — 2-Week Executable Intake Target

### Deliverables
| # | Deliverable |
|---|-------------|
| 1 | Online request end-to-end in browser |
| 2 | OTP send + verify working |
| 3 | Staff reception intake wizard all steps |
| 4 | Customer/vehicle linking |
| 5 | 6-photo camera capture |
| 6 | Diagnosis/service gate |
| 7 | Contract + customer cartable assignment |
| 8 | Customer acceptance in cartable |
| 9 | Staff signature + intake lock |
| 10 | **Ready for controlled JobCard conversion** (gate only — C-2D not executed) |

### DB deliverables
- Live data completion for test requests (operational, not schema)
- Optional: document normalization proposal for cartable table

### Runtime deliverables
- `customer-request.php`, `api/customer/send-otp.php`, `verify-otp.php`
- `erp-reception-intake-file.php`, `intake-save.php`, workbench helper
- `customer-intake-contract-review.php`
- Legacy reception pages marked deprecated in nav

### Browser UAT (mandatory)
| Flow | Pass criteria |
|------|---------------|
| Customer OTP | Code received and verified |
| Wizard steps | All sections green; prerequisites gate passes |
| Cartable | Customer sees contract; accept works |
| Lock | Post-signature immutable |

### Owner signoff
- Intake milestone acceptance form

### Commit rule
**NOT_ELIGIBLE** until all browser UAT rows pass + owner approves split commit (docs vs runtime)

---

## Milestone 2 — 1-Month Operational Core

### Deliverables
| Module | Scope |
|--------|-------|
| JobCard | Controlled conversion from locked intake (C-2D — **owner approval required**) |
| Internal repair | Service operations, steps |
| External repair | Minimum viable vendor tracking (may need new tables) |
| QC | P6 checklist browser path |
| Delivery | P7 invoice + release |
| Service sales | Basic service line billing |

### DB deliverables
- External repair table proposal (if approved)
- JobCard conversion audit columns populated

### Runtime deliverables
- Canonical JobCard entry from reception
- One operational command center per role (dedupe)
- QC + delivery pages browser-proven

### Browser UAT
- JobCard created from intake request
- Operation steps recorded
- QC pass → delivery eligible → customer delivery

### Owner signoff
- Operational core acceptance

### Commit rule
Eligible per-phase after UAT + owner approval; C-2D requires explicit unlock

---

## Milestone 3 — 3-Month ERP Foundation

### Deliverables
| Module | Scope |
|--------|-------|
| Inventory | Stock balances, movements, locations |
| Domestic purchase | PR → approval → receipt path |
| Parts sales | Counter sales MVP |
| Finance base | Cost headers, payments preview |
| HR/admin base | Employee master, attendance |
| Management dashboard | P8 KPI live data |

### DB deliverables
- Inventory domain deduplication decision executed
- Vehicle brand/model master tables
- Populate empty operational tables via real workflows

### Runtime deliverables
- Inventory UI wired to SQL truth
- Purchase request workflow
- Owner dashboard with real counts

### Browser UAT
- Part issued to JobCard reduces stock
- Purchase request approved
- Dashboard reflects open JobCards

### Owner signoff
- ERP foundation gate

### Commit rule
Eligible after module UAT batches

---

## Milestone 4 — 6-Month Sellable Product

### Deliverables
| Module | Scope |
|--------|-------|
| Accounting | Chart of accounts, journals (UAT before claim) |
| Audit | Financial audit reports |
| Logistics/import | Import order MVP |
| UX polish | Luxury UI consistency pass |
| Deployment package | Internal server installer, static IP guide |
| Support docs | Operator manuals (canonical tier) |
| Full UAT | Cross-module regression |

### DB deliverables
- Accounting schema (major — owner approved SQL pack)
- Import/logistics tables

### Runtime deliverables
- Production deployment scripts (not SaaS)
- Accounting UI (preview → activation gate)

### Browser UAT
- Month-end close dry run (internal)
- Import order lifecycle

### Owner signoff
- Sellable product declaration (internal deployment only)

### Commit rule
Eligible with release tagging owner-controlled

---

## Milestone 5 — 9-Month Hardened Product

### Deliverables
| Area | Scope |
|------|-------|
| Security | Audit hardening, access review, secret rotation |
| Multi-company | Configurable company profile (adaptability) |
| Desktop | Electron/Tauri wrapper (if approved) |
| Android APK | WebView/hybrid packaging |
| iPhone/PWA | PWA manifest + install flow |
| Performance | Index tuning, query optimization |
| Backup/recovery | Documented DR procedure |

### DB deliverables
- Performance indexes
- Optional device registration

### Runtime deliverables
- Device-specific builds
- Offline-tolerant paths (if approved)

### Browser UAT + device UAT
- APK smoke test
- PWA install on iOS

### Owner signoff
- Hardened product gate

### Commit rule
Eligible per packaging phase

---

## Phase Template (Required for Every Cursor Task)

Every implementation phase must document:

```markdown
## Phase [ID] — [Name]

### Included
- ...

### Excluded
- ...

### Files allowed to change
- ...

### Files forbidden
- OTP, Auth, DB schema, JobCard (unless unlocked), ...

### Tests
- CLI: ...
- Fixture: supplementary only

### Browser UAT
- [ ] Step 1 ...
- [ ] Step 2 ...

### Commit eligibility
- NOT_ELIGIBLE | ELIGIBLE_AFTER_UAT
```

---

## Critical Path (Current)

```
PR-00 canonical lock
  → GLOBAL_FIX_OTP_FIRST (browser)
  → Intake + cartable browser UAT
  → Owner commit split approval
  → C-2D JobCard conversion (owner unlock)
  → Operational core
  → ERP foundation
  → Sellable → Hardened
```

**Nothing skips OTP and intake browser truth.**

---

## Forbidden Shortcuts

| Shortcut | Why forbidden |
|----------|---------------|
| Fixture-only pass | Does not prove browser |
| Demo RC as completion | Owner target is full ERP |
| Automatic JobCard | Owner-controlled conversion |
| Schema change without proposal | Program reset rule |
| Commit during reset | ~114 open files unstable |

---

**END OF EXECUTION ROADMAP**
