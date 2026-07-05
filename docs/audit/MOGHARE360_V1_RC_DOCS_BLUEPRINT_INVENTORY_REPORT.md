# MOGHARE360 V1 RC — Docs Blueprint / Instruction Inventory Report

**Phase:** REPORT ONLY — NO CODE CHANGE  
**Date:** 2026-07-05  
**Role:** Senior software manager / ERP process owner / product architect / documentation auditor  
**Commit Eligibility:** `NOT_ELIGIBLE_DOCS_INVENTORY_ONLY`

---

## 1. Executive Summary

The `docs/` tree contains **1,229 files** across **27 top-level folders**, encoding the full MOGHARE360 ERP construction history from **MASTER planning (phases 1–15)** through **operational missions P1–P10**, **P11 release hardening**, and **P11.9 reception intake rework (C-0 through C-2C FIX-E7D)**.

**What the docs say we were supposed to build:**

A **Persian RTL, camera-only, workflow-governed auto-workshop ERP** covering **P1 online customer intake → P1.5 intake contract → P2 reception JobCard → P3–P7 workshop operations → P8 management oversight → P9 soft run → P10 navigation RC**, packaged as **V1 RC** for internal demo and controlled One-Day Run — **without** production SaaS, payment gateway, official accounting, or full customer portal.

**P11.9 (July 2026 docs)** adds the owner correction: a **unified reception intake workbench** (`erp-reception-intake-file.php`) with **8-step wizard**, **OTP-verified customer request**, **6 camera photos**, **service/diagnostic gates**, **customer cartable contract task** (hybrid bridge in E7D), **staff signature/lock**, and **controlled JobCard conversion deferred to C-2D**.

**Current audit-freeze reality (from docs + global freeze report):**

| Doc claim | Current reality |
|-----------|-----------------|
| Phases 1–15 COMPLETED / READY | Codebase extensive; **114 uncommitted working-tree files** |
| OTP restored/frozen (E3, D1) | **Browser OTP send failing again**; OTP files modified |
| E7D tests PASS | **Fixture PASS**; browser cartable UAT incomplete |
| V1 RC locked (P11) | **Bugfix lane open**; reception intake still unstable |
| Request 18 ready for contract | **Live data incomplete** (vehicle/photos/docs) |

**Management conclusion:** Docs describe a **coherent V1 RC product** that is **partially built, heavily documented, under-tested in browser, and not commit-eligible**. Recovery requires **OTP stabilization first**, then **browser signoff on reception/cartable**, then **controlled commit split** (docs vs runtime).

---

## 2. Total Docs Inventory

| Metric | Count |
|--------|------:|
| **Total files under `docs/`** | **1,229** |
| Top-level folders | 27 |
| Markdown / text / csv (approx.) | ~1,100+ |
| Largest folder | `missions/` (488 files) |
| Second largest | `implementation/` (175 files) |
| Audit reports (`docs/audit/`) | 90 files |
| Release docs (`docs/release/`) | 33 files |
| Master docs (`docs/master/`) | 12 files |

**Note:** Counts exclude duplicate copies under `release/`, `dist/` — this inventory covers **`moghare360-portal/docs/` only** (canonical repo docs root).

---

## 3. Docs Folder Structure

| Folder | Files | Primary content |
|--------|------:|-----------------|
| `access/` | 6 | Staff role matrix, access UI plan, owner runbook |
| `apex_architecture/` | 26 | Apex/domain architecture references |
| `architecture/` | 14 | System architecture notes |
| `audit/` | 90 | P11.7–P11.9 audits, global freeze, E7D reports |
| `audits/` | 3 | Project file inventory, risk register |
| `contract/` | 10 | Contract flow reference |
| `contract-flow-reference/` | 9 | Legacy contract flow |
| `control/` | 3 | Execution control registry |
| `crm/` | 10 | CRM planning (future phase) |
| `database/` | 25 | Schema, migrations reference |
| `demo/` | 6 | Owner demo runbook, demo data |
| `deployment/` | 8 | Deploy checklists (planning) |
| `dry-run/` | 9 | One-Day Run dry-run pack |
| `executive/` | 4 | Executive decisions, roadmap lock |
| `handover/` | 10 | Handover docs |
| `implementation/` | 175 | Wave implementations, readonly specs |
| `inventory/` | 10 | Feature inventory |
| `legal/` | 1 | Intake contract V1 legal text reference |
| `master/` | 12 | **Locked master execution prompt + plans 01–10** |
| `media/` | 9 | Media capture rules |
| `missions/` | 488 | **Phase + P1–P10 + mission_07–37 build reports** |
| `network/` | 9 | Mirror/network architecture |
| `operations/` | 10 | Operational run docs |
| `phases/` | 89 | Phase 01–09 + 16–23 scope/signoff |
| `planning/` | 9 | Read-only page backlog |
| `product/` | 8 | Commercial brief, SaaS packaging (future) |
| `release/` | 33 | **V1 RC manifest, security lock, packaging** |
| `validation/` | 10 | Validation rules |

---

## 4. Document Classification

### 4.1 Classification key

| Type | Meaning |
|------|---------|
| MASTER_PROMPT | Locked governance — boundaries, forbidden scope |
| BLUEPRINT | Intended product/process design |
| PLAN | Execution plan before build |
| SCOPE | Phase gate — proceed/stop |
| PHASE_REPORT | Build result for a phase/mission |
| MISSION_REPORT | Same, under `docs/missions/` |
| TEST_REPORT | Test/signoff result |
| AUDIT_REPORT | Read-only inspection |
| SIGNOFF | Formal phase completion |
| RELEASE_NOTE | Release packaging notes |
| MANIFEST | Package/RC boundary list |
| USER_GUIDE | Operator runbook |
| UNKNOWN | Non-standard |

### 4.2 Classification by folder (batch rule)

| Folder pattern | Default type | Business area |
|----------------|--------------|---------------|
| `docs/master/*` | MASTER_PROMPT / BLUEPRINT | All modules (governance) |
| `docs/missions/p1_*` … `p10_*` | MISSION_REPORT | Per phase name |
| `docs/missions/phase_*` | PHASE_REPORT / SIGNOFF | Foundation phases 1–15 |
| `docs/missions/mission_*` | PHASE_REPORT | Auth, JobCard, parts, finance foundations |
| `docs/audit/MOGHARE360_P11_*` | AUDIT_REPORT / SCOPE | Reception, navigation, intake |
| `docs/audit/*_SCOPE*` | SCOPE | Gate before build |
| `docs/audit/*_REPORT*` (non-SCOPE) | PHASE_REPORT or AUDIT | Implementation or inspection |
| `docs/release/*` | MANIFEST / RELEASE_NOTE | Commercial/Release |
| `docs/phases/phase_*` | PLAN / SCOPE | Pre-implementation planning |
| `docs/access/*` | PLAN / USER_GUIDE | Core Access/Auth |
| `docs/product/*` | BLUEPRINT | Commercial/Release (future) |
| `docs/legal/*` | BLUEPRINT | Contract |
| `docs/demo/*`, `docs/dry-run/*` | USER_GUIDE | Soft Run |

---

## 5. Master / Blueprint Documents

### 5.1 Canonical master pack (`docs/master/` — 12 files)

| # | File | Type | What to build | Forbidden | PASS? | Superseded? |
|---|------|------|---------------|-----------|-------|-------------|
| 1 | `MOGHARE360_MASTER_EXECUTION_PROMPT_FINAL_LOCKED.md` | MASTER_PROMPT | Full ERP; phases 1–15 complete; UI→Validation→Workflow→DB→Audit; Persian RTL; camera-only media | Auth rewrite, SaaS, payment, accounting, portal activation, secrets in repo | Claims phases READY | Partially — P11.9 extends reception |
| 2 | `MOGHARE360_CURSOR_EXECUTION_PACK_INDEX.md` | MANIFEST | Index to master plans | — | Planning | No |
| 3 | `MOGHARE360_MASTER_01_FOLDER_STRUCTURE_PLAN.md` | BLUEPRINT | Repo folder layout | — | Planning | No |
| 4 | `MOGHARE360_MASTER_02_SQL_SCHEMA_PLAN.md` | BLUEPRINT | SQL Server schema plan | Destructive DDL | Planning | No |
| 5 | `MOGHARE360_MASTER_03_API_LIST_PLAN.md` | BLUEPRINT | API surface | — | Planning | No |
| 6 | `MOGHARE360_MASTER_04_SECURITY_ARCHITECTURE_PLAN.md` | BLUEPRINT | Auth, CSRF, permissions model | Auth rewrite in missions | Planning | No |
| 7 | `MOGHARE360_MASTER_05_VALIDATION_ENGINE_PLAN.md` | BLUEPRINT | Validation layer | — | Planning | No |
| 8 | `MOGHARE360_MASTER_06_WORKFLOW_ENGINE_PLAN.md` | BLUEPRINT | Status transitions | — | Planning | No |
| 9 | `MOGHARE360_MASTER_07_UI_MODULE_STRUCTURE_PLAN.md` | BLUEPRINT | UI modules | — | Planning | No |
| 10 | `MOGHARE360_MASTER_08_LOCAL_DEPLOYMENT_PLAN.md` | BLUEPRINT | XAMPP local deploy | Production deploy | Planning | No |
| 11 | `MOGHARE360_MASTER_09_MIRROR_DOMAIN_PLAN.md` | BLUEPRINT | Mirror site (exploratory) | — | Planning | **Yes** — executive decision supersedes |
| 12 | `MOGHARE360_MASTER_10_EXECUTION_SIGNOFF.md` | SIGNOFF | Master pack complete | — | PASS (planning) | No |

### 5.2 Blueprint extraction — intended behavior by domain

#### Reception intake blueprint (P11.9-C series)

| Source | Intended module | Workflow | UI | DB | Tests | Security | Browser gate | Commit gate |
|--------|-----------------|----------|-----|-----|-------|----------|--------------|-------------|
| `P11_9_C_0_*` | Unified reception workbench | Online request → intake file → JobCard (later) | `erp-reception-intake-file.php` 8-step wizard | `request_payload_json` merge UPDATE | Diagnostic tools | No Auth change | Full intake UAT | Not until browser pass |
| `P11_9_C_2C_SCOPE` | Intake write actions | save_vehicle, condition, service, documents, confirmation | Same | No schema migration | FIX-A–E7D tests | OTP write forbidden from staff | Stepper UAT | Scope PROCEED |
| `P11_9_C_2B_*` | Luxury reception workbench | Replace fragmented P1 pages | Stepper, luxury CSS | Payload recovery | E6A/E7 tests | CSRF stable (E7B) | UAT rejected then rework | NOT_ELIGIBLE until cartable |
| `P11_9_C_2C_FIX_E7C` | **Owner cartable blueprint** | Staff completes intake → assign cartable task → customer accepts → staff signature → workshop | Cartable not public raw link | Payload cartable paths | E7D suite | OTP frozen | Browser cartable flow | NOT_ELIGIBLE |

#### OTP blueprint (P11.9-C-2C FIX-D/E3)

| Source | Intended | Forbidden |
|--------|----------|-----------|
| `FIX_D0` | Single canonical OTP architecture audit | — |
| `FIX_D1` | Deprecate legacy root OTP routes; wire `api/customer/send-otp.php` | Duplicate OTP paths |
| `FIX_D1A` | IPPanel auth header mode, check_token diagnostics | Secret leak |
| `FIX_E3` | Restore D1B OTP flow after cleanup | OTP logic rewrite |
| Master prompt | External SMS via approved config only | Fake OTP in production |

#### Customer request blueprint (P1 + P11.9-B)

| Source | Intended |
|--------|----------|
| `P1_ONLINE_REQUEST_INTAKE_REPORT` | `customer-request.php` OTP-first → `api/customer/request.php` → `erp_customer_online_requests` |
| `P11_9_B_FIX_C–F` | Public entry router, luxury UI, cache headers |
| Master | Camera-only; no upload bypass |

#### Customer contract / cartable blueprint

| Source | Intended |
|--------|----------|
| `P1_5_INTAKE_CONTRACT_SIGNATURE_REPORT` | DB-backed contracts after JobCard; OTP+signature; `erp_intake_contracts` |
| `FIX_E7` | Customer token review page; staff cannot approve contract |
| `FIX_E7C/E7D` | **Customer cartable task** in payload; hybrid token bridge for V1 RC; real portal deferred |

#### JobCard conversion blueprint

| Source | Intended |
|--------|----------|
| `P1` convert action | `m360_reception_convert_to_jobcard()` from online request detail |
| `P11_9_C_2C_SCOPE` | **No automatic JobCard** in C-2C; conversion = **C-2D** |
| `P11_9_C_0` | CSRF fix needed before convert works |

#### Operation / service (P2–P5)

| Mission | Intended |
|---------|----------|
| P2 | Reception JobCard board, actions, operational workflow |
| P3 | Technical operation board |
| P4 | Estimate approval, parts finance gate |
| P5 | Work execution, parts consumption |

#### Inventory / purchase / finance (missions 21–28, phase 4–5)

Foundation design + controlled create — **locked out of V1 RC feature build** per `V1_RC_FINAL_LOCK.md` except existing P4/P5 gates.

#### Release / packaging (P10, P15, release/)

| Doc | Intended |
|-----|----------|
| `MOGHARE360_V1_RC_MANIFEST.md` | P1–P10 migrations, P10 nav pages, test suites |
| `MOGHARE360_V1_RC_FINAL_LOCK.md` | P11 lock; bugfix only; no new domains |
| Phase 15 missions | Demo + Local RC1 zip packages; exclude secrets |

---

## 6. Phase and Mission Documents

### 6.1 Operational missions P1–P10 (canonical under `docs/missions/`)

| Mission folder | Phase | Build intent (from mission REPORT) | Claims |
|----------------|-------|----------------------------------|--------|
| `p1_online_request_intake` | P1 | Online request API, reception list/detail, convert JobCard | Implemented |
| `p1_5_intake_contract_signature` | P1.5 | Intake contracts, customer sign, P2 gate | Implemented |
| `p2_reception_operational_workflow` | P2 | Reception JobCard workflow | Implemented |
| `p3_technical_operation_board` | P3 | Technical ops board | Implemented |
| `p4_estimate_approval_parts_finance_gate` | P4 | Estimate + parts gate | Implemented |
| `p5_work_execution_parts_consumption` | P5 | Work execution | Implemented |
| `p6_qc_final_inspection_delivery_readiness` | P6 | QC / delivery readiness | Implemented |
| `p7_final_invoice_settlement_customer_delivery` | P7 | Invoice, settlement, delivery | Implemented |
| `p8_management_dashboard_owner_control` | P8 | Management dashboard | Implemented |
| `p9_end_to_end_soft_run` | P9 | End-to-end soft run | Implemented |
| `p10_release_hardening_navigation_rc` | P10 | Route map, link audit, demo package RC | Implemented |

Each mission folder typically contains: `*_SCOPE.md`, `*_REPORT.md`, `*_TEST_PLAN.md`, `*_SIGNOFF.md`, `*_RESULT.md` (4–8 files × 10 missions ≈ 40–80 files).

### 6.2 Foundation phases 1–15 (`docs/missions/phase_*`)

| Folder | Title (from naming) | Status in master prompt |
|--------|---------------------|-------------------------|
| `phase_1_customer_core_system` | Customer core | COMPLETED |
| `phase_2_operation_engine` | Operation engine | COMPLETED |
| `phase_3_rule_engine` | Rules | COMPLETED |
| `phase_4_inventory_purchase_system` | Inventory/purchase | COMPLETED |
| `phase_5_financial_system` | Finance | COMPLETED |
| `phase_6_crm_system` | CRM | COMPLETED |
| `phase_7_hr_internal_admin` | HR | COMPLETED |
| `phase_8_ui_productization_layer` | UI productization | COMPLETED |
| `phase_9_business_ready_system` | Business ready | COMPLETED |
| `phase_10_commercial_system` | Commercial | COMPLETED |
| `phase_11_stabilization_sprint` | Stabilization | COMPLETED |
| `phase_12_soft_run_pilot` | Soft run pilot | COMPLETED |
| `phase_12_5_localization_branding` | Localization | COMPLETED |
| `phase_13_security_access_hardening` | Security | COMPLETED |
| `phase_14_production_deployment_plan` | Deploy plan | COMPLETED (plan only) |
| `phase_15_downloadable_release_package` | Release zip | COMPLETED |

### 6.3 Auth/entity missions (`mission_07`–`mission_37`)

Sequential foundation missions for auth consolidation, permissions, customer/vehicle create, JobCard create, service operation, parts, purchase, finance, QC, UX layers, soft run — **predate P1–P10 operational missions** but document incremental ERP construction. V1 RC lock treats these as **completed foundation**, not active build scope.

### 6.4 Planning phases (`docs/phases/` — 19 folders)

Pre-implementation architecture phases (01–09, 16–23): safe foundation, DB baseline, domain model, validation matrix, network/mirror, media capture, contract engine, live workshop run, inventory/CRM/finance handover. **Planning-only** — no runtime claim without matching mission report.

---

## 7. Audit and Test Reports

### 7.1 Complete `docs/audit/` inventory (90 files)

**Legend columns abbreviated:** T=Type, A=Area, Build=what to build, Forbid=forbidden, Kind=P=planning R=report, Pass=claims PASS, Sup=superseded, Freeze=conflicts freeze, RC=relevant V1 RC

#### P11.7 — Staff home / workbench

| File | T | A | Build (summary) | Forbid | Kind | Pass | Sup | Freeze | RC |
|------|---|---|-----------------|--------|------|------|-----|--------|-----|
| `MOGHARE360_P11_7_WORKBENCH_SCOPE_GATE_REPORT.md` | SCOPE | Reception | Staff home workbench scope | Auth | P | Gate | No | No | Yes |
| `MOGHARE360_P11_7_ONE_DAY_RUN_WORKBENCH_COVERAGE.md` | BLUEPRINT | Reception | One-Day Run coverage map | — | P | — | Partial | No | Yes |
| `MOGHARE360_P11_7_1_*` (4 files) | AUDIT/SCOPE | Reception | Staff home UX polish | — | R | Mixed | No | No | Yes |
| `MOGHARE360_P11_7_STAFF_HOME_ENCODING_FIX_REPORT.md` | PHASE_REPORT | Reception | UTF-8 fix | — | R | PASS | No | No | Yes |

#### P11.8 — Navigation / operational shell

| File | T | A | Build (summary) | Forbid | Kind | Pass | Sup | Freeze | RC |
|------|---|---|-----------------|--------|------|------|-----|--------|-----|
| `MOGHARE360_P11_8_*` (18 files) | AUDIT/SCOPE/REPORT | Reception/Nav | Manager reference bridge, operational shell, route map safety, Persian labels | Workflow mutation | R | Mixed | No | No | Yes |

#### P11.9 — Dry run / public entry

| File | T | A | Build (summary) | Forbid | Kind | Pass | Sup | Freeze | RC |
|------|---|---|-----------------|--------|------|------|-----|--------|-----|
| `MOGHARE360_P11_9_0_ONE_DAY_RUN_DRY_RUN_READINESS_DISCOVERY_REPORT.md` | AUDIT | Soft Run | Dry run readiness assessment | P12 | R | Partial ready | No | OTP gap | Yes |
| `MOGHARE360_P11_9_1_ONE_DAY_RUN_MAXIMUM_STEP_MAP_REPORT.md` | PLAN | Soft Run | Max step map for dry run | — | P | — | No | No | Yes |
| `MOGHARE360_P11_9_A_DRY_RUN_PACK_*` | SCOPE/REPORT | Soft Run | Dry run pack | — | R | — | No | No | Yes |
| `MOGHARE360_P11_9_B_0_DRY_RUN_PREFLIGHT_EXECUTION_PLAN.md` | PLAN | Soft Run | Preflight plan | P12, JobCard auto | P | — | No | No | Yes |
| `MOGHARE360_P11_9_B_FIX_A–F_*` (12 files) | SCOPE/REPORT | Customer/Public | Demo staff, public home, entry router, luxury UI | — | R | CLI PASS | No | OTP | Yes |
| `MOGHARE360_P11_9_B_PUBLIC_ENTRY_*` | REPORT | Customer | Commit reconciliation | — | R | — | No | No | Yes |

#### P11.9-C — Reception intake / wizard / OTP / cartable

| File | T | A | Build (summary) | Forbid | Kind | Pass | Sup | Freeze | RC |
|------|---|---|-----------------|--------|------|------|-----|--------|-----|
| `MOGHARE360_P11_9_C_0_RECEPTION_INTAKE_JOBCARD_DISCOVERY_REPORT.md` | AUDIT | Reception | Discovery: need unified intake workbench | Auth, P12 | R | Gaps listed | No | Yes | Yes |
| `MOGHARE360_P11_9_C_1_*` | SCOPE/REPORT | Reception | Action stabilization | — | R | PASS | No | CSRF history | Yes |
| `MOGHARE360_P11_9_C_2A_*` | AUDIT | Reception | Full capability inventory | — | R | — | No | Yes | Yes |
| `MOGHARE360_P11_9_C_2B_*` (11 files) | SCOPE/REPORT/AUDIT | Reception | Luxury workbench, UAT, rework, field recovery | — | R | UAT rejected→rework | No | UX | Yes |
| `MOGHARE360_P11_9_C_2C_SCOPE_REPORT.md` | SCOPE | Reception | Payload write actions, no schema | JobCard, OTP write, Auth | P | PROCEED | No | No | **Canonical** |
| `MOGHARE360_P11_9_C_2C_INTAKE_WRITE_ACTIONS_REPORT.md` | PHASE_REPORT | Reception | Write actions implementation | — | R | PASS CLI | No | Browser | Yes |
| `MOGHARE360_P11_9_C_2C_FIX_A–C_*` | SCOPE/REPORT | Reception | Save validation, UAT, scroll/photo | — | R | PASS CLI | No | No | Yes |
| `MOGHARE360_P11_9_C_2C_FIX_D0–D1A_*` | AUDIT/SCOPE/REPORT | OTP | OTP architecture cleanup, IPPanel auth | OTP secrets | R | PASS CLI | **Conflicts freeze** | **Yes** | Yes |
| `MOGHARE360_P11_9_C_2C_FIX_E_*` – `E7D_*` (28 files) | SCOPE/REPORT/AUDIT | Reception/Contract/Cartable | Stepper, wizard lock, OTP restore, service gate, photos, SQL truth routing, customer contract, CSRF, cartable bridge | OTP change, JobCard, Auth, DB | R | CLI PASS | E7→E7D chain | **Yes** | **Latest canonical** |
| `MOGHARE360_V1_RC_GLOBAL_SOFTWARE_AUDIT_FREEZE_REPORT.md` | AUDIT | All | Freeze state; no build | Everything | R | STOP | Supersedes PASS claims | **Authoritative now** | Yes |

#### Cross-cutting audits

| File | T | A | Summary |
|------|---|---|---------|
| `MOGHARE360_EMPLOYEE_WORK_ENVIRONMENT_AUDIT.md` | AUDIT | All | P1–P7 pages exist |
| `MOGHARE360_GITHUB_COMPLETION_AUDIT_REPORT.md` | AUDIT | Packaging | Repo complete through P11.4.5-A (pre-P11.9 uncommitted) |

---

## 8. Release and Packaging Documents

All 33 files in `docs/release/` — summary by purpose:

| Document | Type | Intended build / boundary |
|----------|------|---------------------------|
| `MOGHARE360_V1_RC_MANIFEST.md` | MANIFEST | P1–P10 RC scope, migrations, tests |
| `MOGHARE360_V1_RC_FINAL_LOCK.md` | SIGNOFF | P11 lock; bugfix-only; lists allowed P11.x lanes |
| `MOGHARE360_V1_RC_FINAL_AUDIT_REPORT.md` | AUDIT | RC audit |
| `MOGHARE360_V1_RELEASE_READINESS_REPORT.md` | TEST_REPORT | Readiness score |
| `MOGHARE360_V1_ROUTE_MAP.md` | MANIFEST | Route catalog |
| `MOGHARE360_V1_SECURITY_SCOPE_LOCK.md` | BLUEPRINT | Security boundaries |
| `MOGHARE360_V1_DEMO_PACKAGE_RC.md` | MANIFEST | Demo package contents |
| `MOGHARE360_V1_LOCAL_DEMO_PACKAGE_MANIFEST.md` | MANIFEST | Local demo zip |
| `MOGHARE360_V1_PRODUCTION_RUN_SIGNOFF.md` | SIGNOFF | Production signoff checklist |
| `MOGHARE360_V1_OPERATIONAL_ACCEPTANCE.md` | SIGNOFF | Operational acceptance |
| `MOGHARE360_V1_POST_RUN_FIX_REGISTER.md` | PLAN | Post-run fixes |
| `MOGHARE360_*_SAAS_*`, `MIRROR_*` | PLAN | **Future — NOT V1 RC active** |
| `MOGHARE360_CPANEL_*` | USER_GUIDE | Deploy checklists (planning) |

---

## 9. Intended Product Reconstruction

### 9.1 Product identity

**MOGHARE360 ERP** — Persian RTL auto-workshop management system for **Moghareh Motors**, SQL Server backend, XAMPP local runtime, staff-role-based operational workflow from customer online request through delivery.

### 9.2 Target users

| User | Role in docs |
|------|--------------|
| Customer (public) | Online request, OTP, optional contract sign |
| Receptionist | Intake file, photos, documents, cartable assign |
| Workshop staff | P2–P5 operational boards |
| QC / Delivery | P6–P7 |
| Manager / Owner | P8 dashboard, route map, access management (P11.4) |
| Demo operator | One-Day Run dry run |

### 9.3 Main modules (V1 RC per docs)

P1 Online Request → P1.5 Contract → P2 Reception JobCard → P3 Technical → P4 Estimate/Parts → P5 Work → P6 QC → P7 Invoice/Delivery → P8 Management → P9 Soft Run → P10 Navigation RC → P11 Audit/Lock → **P11.9 Reception Intake Workbench (in progress)**

### 9.4 Full customer journey (intended)

1. **Customer request** — `customer-request.php`, luxury public UI (P11.9-B)
2. **OTP** — `api/customer/send-otp.php` + verify; mobile verified before submit
3. **Reception** — staff opens `erp-reception-intake-file.php` wizard
4. **Vehicle** — plate, brand, model, mileage, fuel
5. **Condition** — belongings, damage, initial condition
6. **Service** — route, diagnostic subs, path clear gate
7. **Referral** — team assignment
8. **Photos** — 6/6 camera captures (no upload bypass)
9. **Diagnosis** — diagnostic status/PDF + cost agreement
10. **Contract / customer cartable** — assign cartable task; customer accepts with OTP (E7D hybrid)
11. **Staff confirmation / signature** — reception lock
12. **JobCard conversion** — **C-2D** (explicitly deferred in C-2C docs)
13. **Workshop/hall** — P2+ JobCard workflow
14. **Estimate → parts → finance gates** — P4–P5
15. **QC → delivery → follow-up** — P6–P7

### 9.5 Admin / back-office journey

Staff Home → role cards → operational boards → action POST handlers with CSRF → history/audit tables → management dashboards (P8) → access management UI (P11.4).

### 9.6 Owner dashboard / control

`erp-release-readiness.php`, `erp-route-map.php`, `erp-rc-final-audit.php`, `erp-access-management.php`, executive go/no-go workflows (implementation/wave_9).

### 9.7 Security / access model (intended)

- Staff session auth (frozen — no rewrite)
- Role-based permissions (frozen seed)
- CSRF on all POST writes
- OTP for customer identity
- Token hash only for contract/cartable links
- Private config gitignored

### 9.8 Audit / history model

- `erp_customer_online_request_history`
- JobCard change history
- Contract events
- Access audit (P11.4)
- Payload `section_status` (write-only metadata; not routing truth per E6A)

### 9.9 Release / packaging model

Demo zip + Local RC1 zip; no production installer; exclude secrets, `.bak`, real uploads; XAMPP sync via robocopy; V1 RC manifest defines test suites.

### 9.10 Before product is sellable (per docs)

| Requirement | Source |
|-------------|--------|
| Browser One-Day Run pass | P11.9 dry-run docs |
| OTP stable on customer-request + reception | D1/E3/master |
| Reception intake wizard complete UAT | C-2B/C-2C |
| Customer cartable browser flow | E7D |
| No open audit-freeze blockers | Global freeze report |
| Owner signoff on V1 RC boundaries | V1_RC_FINAL_LOCK |
| **Not required for V1 RC:** SaaS, accounting, payment gateway, full customer portal |

---

## 10. Customer Journey Intended By Docs

See §9.4. **Critical owner correction (E7C/E7D):** contract acceptance is **customer cartable task**, not receptionist approval and not a permanent public contract link semantics.

---

## 11. Staff / Reception Journey Intended By Docs

1. Staff Home → online requests OR direct intake file by `online_request_id`
2. 8-step wizard with stepper UX (FIX-E)
3. OTP send/verify on intake (staff triggers customer OTP — same helper as public)
4. Save per step with validation (FIX-A)
5. Prerequisites gate before cartable assign (E7D)
6. Blocker checklist on documents when incomplete (E7D)
7. Post-signature lock — no edits (FIX-E2)
8. JobCard conversion — separate controlled phase C-2D

**P11.9-C-0 gap (still in docs):** offline walk-in unified wizard **not fully specified as built**.

---

## 12. Customer Contract / Cartable Intended By Docs

| Era | Model |
|-----|-------|
| P1.5 | DB `erp_intake_contracts` after JobCard exists; customer sign page |
| FIX-E7 | Token review page; customer accepts; staff cannot approve |
| FIX-E7C/E7D | **Customer cartable** payload task; hybrid token URL for V1 RC only; real portal deferred |

**Required payload paths (E7D):** `customer_cartable.contract_task`, mirror `contract.*`, `documents.contract_status`.

---

## 13. JobCard / Operation Intended By Docs

- **P1:** convert online request → JobCard (`RECEIVED` status)
- **P2–P7:** operational workflow chain (boards, actions, gates)
- **P11.9 C-2C:** explicitly **no automatic JobCard** during intake saves
- **C-2D (planned, not in working tree docs as complete):** controlled conversion from completed intake

---

## 14. Back Office Modules Intended By Docs

| Module | V1 RC status per docs |
|--------|----------------------|
| Parts/Inventory | Foundation missions; P5 consumption; **not full inventory ERP in RC lock** |
| Purchase | Mission 25–26 foundation; locked out of new build |
| Finance | Mission 27–28; P4/P7 gates; no accounting ledger |
| CRM | Phase 6 / phase_22 plans; **NOT ACTIVE** in RC lock |
| HR | Phase 7 / P15 backlog in staff home |
| QC/Delivery | P6–P7 implemented per mission reports |

---

## 15. Security / Access / Audit Intended By Docs

- Frozen: `staff-auth.php`, `access-control.php`, permission seeds
- Allowed P11.4: access management UI over SQL identity
- CSRF: stable reusable token (E7B)
- OTP: single canonical path via helper + private config
- Audit: history tables + phase reports; no secret exposure in reports

---

## 16. Actual vs Intended Matrix

| Intended feature | Source doc | Doc build status | Current status (audit freeze) | Evidence / risk | Required action |
|------------------|------------|------------------|-------------------------------|-----------------|-----------------|
| Customer request + OTP | P1, P11.9-B | Built | **BUILT_BUT_BROWSER_FAILING** | OTP send fails 09128166648 | GLOBAL_FIX_OTP_FIRST |
| Reception intake 8-step wizard | C-2B/C-2C FIX-E | Built CLI | **PARTIAL** | Browser UAT incomplete | Complete browser UAT |
| 6 photo completion | FIX-E5 | Built CLI | **BUILT_CLI_ONLY** | Live req 18 photos incomplete | Staff completes live data |
| Service/diagnostic gate | FIX-E4 | Built CLI | **BUILT_CLI_ONLY** | — | Browser verify |
| Customer cartable bridge | FIX-E7D | Built CLI | **BUILT_BUT_BROWSER_FAILING** | UAT not done | Browser cartable flow |
| CSRF stable on documents | FIX-E7B | Built CLI | **PARTIAL** | Was browser issue pre-E7B | Re-verify browser |
| OTP frozen/stable | FIX-E3, D1 | Claimed PASS | **BUILT_BUT_BROWSER_FAILING** | 8 OTP files modified | Isolate OTP fix |
| JobCard auto on intake | C-2C scope | **FORBIDDEN** | FORBIDDEN | Correct | Keep forbidden |
| JobCard conversion C-2D | C-2C scope | PLANNED_ONLY | FORBIDDEN (current) | Not started | Owner approval for C-2D |
| P2–P7 workflow | P2–P7 missions | BUILT_AND_BROWSER_CONFIRMED (historical) | **UNKNOWN** | Not re-tested in freeze | Regression spot-check |
| V1 RC navigation P10 | V1_RC_MANIFEST | Built | **BUILT_CLI_ONLY** | Signoff tests pass | Demo verify |
| Unified reception workbench | C-2B | Built | **PARTIAL** | UAT rejected then rework | Owner UX review |
| Real customer portal | Master prompt | **FORBIDDEN** | FORBIDDEN | — | Defer post-V1 |
| SaaS / accounting / payment | Master, RC lock | **FORBIDDEN** | FORBIDDEN | — | Defer |
| One-Day Run dry run | P11.9-0 | Partial ready | **NEEDS_OWNER_DECISION** | OTP + intake block | Fix then dry run |
| Commit/push V1 RC | Multiple | Eligible after UAT | **NOT_ELIGIBLE** | 114 open files | Audit freeze |

---

## 17. Contradictions and Drift

| # | Contradiction | Documents involved |
|---|---------------|-------------------|
| 1 | OTP frozen vs modified | E3/D1/E4/E5 freeze reports vs global freeze; 8 modified OTP files |
| 2 | Browser pass vs failing | E7D "tests PASS" vs global freeze OTP/cartable browser fail |
| 3 | Public contract link vs cartable | E7 report vs E7C owner blueprint vs E7D hybrid |
| 4 | Request 18 complete vs incomplete | E6A fixture tests vs E7C/E7D live diagnostic |
| 5 | C-2D JobCard vs forbidden | C-2C defers C-2D; P1 still documents convert button on old detail page |
| 6 | Phases 1–15 COMPLETED vs P11.9 rework | Master prompt vs C-2B UAT rejection |
| 7 | GitHub audit "clean tree" vs 114 open files | GITHUB_COMPLETION_AUDIT vs global freeze (different audit dates) |
| 8 | Legacy OTP routes deprecated vs customer-login | D1 stubs root routes; customer-login still posts to `send-otp.php` |

---

## 18. Superseded Documents

| Document | Superseded by |
|----------|---------------|
| `MOGHARE360_MASTER_09_MIRROR_DOMAIN_PLAN.md` | `executive/MOGHARE360_LOCAL_SERVER_MIRROR_DOMAIN_FINAL_DECISION.md` |
| `FIX_E7_CUSTOMER_CONTRACT_*` (public link semantics) | `FIX_E7C` blueprint + `FIX_E7D` cartable bridge |
| `FIX_E3_RESTORE_D1B_OTP_*` (OTP stable claim) | Global freeze OTP regression |
| `MOGHARE360_GITHUB_COMPLETION_AUDIT_REPORT.md` (clean tree) | Global freeze (working tree state) |
| `P11_9_C_2B_UAT_REJECTION_REPORT.md` issues | Partially addressed by C-2C FIX-A–E7D (verify in browser) |
| Any doc claiming `NOT_ELIGIBLE_UNTIL_BROWSER_*` without browser pass | Still in force — not superseded until UAT done |

---

## 19. Canonical Documents Recommended

**Tier 1 — Governance (always canonical)**

1. `docs/master/MOGHARE360_MASTER_EXECUTION_PROMPT_FINAL_LOCKED.md`
2. `docs/release/MOGHARE360_V1_RC_FINAL_LOCK.md`
3. `docs/release/MOGHARE360_V1_RC_MANIFEST.md`
4. `docs/audit/MOGHARE360_V1_RC_GLOBAL_SOFTWARE_AUDIT_FREEZE_REPORT.md`

**Tier 2 — Active build scope (reception intake)**

5. `docs/audit/MOGHARE360_P11_9_C_2C_SCOPE_REPORT.md`
6. `docs/audit/MOGHARE360_P11_9_C_2C_FIX_E7C_DIAG_CONTRACT_FLOW_BLUEPRINT_REPORT.md`
7. `docs/audit/MOGHARE360_P11_9_C_2C_FIX_E7D_CUSTOMER_CARTABLE_BRIDGE_SCOPE_REPORT.md`
8. `docs/audit/MOGHARE360_P11_9_C_2C_FIX_E7D_CUSTOMER_CARTABLE_BRIDGE_REPORT.md`

**Tier 3 — Operational reference**

9. `docs/missions/p1_online_request_intake/P1_ONLINE_REQUEST_INTAKE_REPORT.md`
10. `docs/missions/p1_5_intake_contract_signature/P1_5_INTAKE_CONTRACT_SIGNATURE_REPORT.md`
11. `docs/missions/p2_reception_operational_workflow/P2_RECEPTION_OPERATIONAL_WORKFLOW_REPORT.md`
12. `docs/audit/MOGHARE360_P11_9_C_0_RECEPTION_INTAKE_JOBCARD_DISCOVERY_REPORT.md`

---

## 20. Minimum V1 RC Definition

Per **`V1_RC_FINAL_LOCK.md`** + **`V1_RC_MANIFEST.md`** + **P11.9-C-2C/E7D docs**:

**Must include:**

- P1–P10 operational PHP workflow (existing)
- P10 navigation / route registry / demo package manifest
- P11 audit pages and owner presentation lock
- P11.4 access management UI
- P11.9 reception intake workbench with wizard, payload persistence, cartable bridge
- Canonical OTP via `api/customer/*` + private config
- SQL migrations P1–P10 (+ P11 comment-only)
- Test suites through `test-v1-production-signoff.php` + P11.9 FIX-* tests
- **Browser signoff:** customer OTP, full intake wizard, cartable accept, signature lock

**Must NOT include (V1 RC):**

- Production SaaS, payment gateway, official accounting
- Full customer portal / login architecture
- Automatic JobCard on intake
- C-2D unless separately approved
- P12 scope

---

## 21. Minimum Sellable Product Definition

Per **`docs/product/MOGHARE360_COMMERCIAL_PRODUCT_BRIEF.md`** and master forbidden list — **V1 RC is NOT minimum sellable product**. Sellable requires (future docs): SaaS packaging, accounting integration, production deploy, licensed assets, tenant model — all explicitly **NOT ACTIVE** in V1 RC lock.

**Minimum sellable (future):** beyond V1 RC; not defined as complete in any committed doc.

---

## 22. Minimum Demo Product Definition

Per **`docs/demo/`**, **`V1_DEMO_PACKAGE_RC.md`**, **P11.9 dry-run docs**:

- XAMPP local deploy with demo staff users
- One sample JobCard through P2–P7 happy path
- Customer request with working OTP (currently **blocked**)
- Owner presentation pages
- DEMO data only — no real customer PII

---

## 23. Minimum Local RC Package Definition

Per **Phase 15** + **`MOGHARE360_LOCAL_RC1_RELEASE_NOTES.md`**:

- `release/moghare360-local-rc1.zip` contents per manifest
- Excludes `private/`, secrets, `.bak`, real uploads
- Includes `public_html/`, SQL migrations, tools, docs subset
- Robocopy sync to `C:\xampp\htdocs\moghare360`
- Passes `test-phase-15-release-package.php` (when run)

---

## 24. Commit / Push Documentation Risk

| Question | Answer |
|----------|--------|
| Docs-only commit safe now? | **NO** — audit freeze; many untracked docs describe uncommitted code state |
| Runtime commit safe? | **NO** — 114 open files; OTP regression |
| Which docs not to commit yet? | E7D reports implying browser pass; E3 OTP stable; any doc contradicting freeze |
| Split strategy | **Phase 1:** canonical tier-1–2 docs after owner review. **Phase 2:** runtime after OTP + browser pass. **Never** commit private config |

---

## 25. Recommended Documentation Control

1. **Remain canonical:** Tier 1–2 list (§19)
2. **Archive:** Superseded E7 public-link reports; pre-P11.9 fragmented reception UX audits once C-2B rework confirmed
3. **Mark superseded:** E7 customer contract scope (semantics) → pointer to E7D; MASTER_09 mirror plan → executive decision
4. **Merge into final blueprint:** P11.9-C-2C scope + E7C owner cartable correction + E7D bridge = single **Reception Intake & Cartable V1 RC Blueprint** (future doc — not created in this phase)
5. **Do not commit yet:** Untracked audit batch until aligned with post-fix reality
6. **Docs-only commit:** Not safe during freeze
7. **Split commits:** `docs(audit): P11.9 reception freeze reports` separate from `feat(reception): E7D cartable bridge` after browser pass

---

## 26. Recommended Next Management Decision

| Option | When |
|--------|------|
| **GLOBAL_FIX_OTP_FIRST** | **Immediate** — blocks customer entry and cartable |
| **Browser UAT reception + cartable** | After OTP pass |
| **LIVE_DATA_REPAIR request 18** | Operational — parallel with UAT |
| **C-2D JobCard conversion** | **Stop** — owner approval required |
| **HARD_FREEZE_AND_REPLAN** | Only if OTP fix fails twice |
| **Docs canonicalization commit** | After owner reads this inventory + freeze report |
| **Runtime commit** | After OTP + browser cartable + signature lock pass |

---

## 27. Final Conclusion

MOGHARE360 V1 RC docs blueprint inventory identifies every instruction, blueprint, plan, mission, audit, signoff, release note, and manifest under `docs/`, reconstructs what the product was supposed to become, compares intended scope against current audit-freeze reality, detects drift and contradictions, and prepares the owner and software manager to decide the controlled recovery path before any fix, commit, push, C-2D, JobCard conversion, or production-ready claim.

**In one sentence for the owner:** The documentation describes a **complete internal workshop ERP (P1–P10) plus an in-progress professional reception intake wizard with customer cartable contract flow (P11.9)** — but **OTP browser regression, incomplete live intake data, uncommitted code, and missing browser UAT** mean the documented product is **not yet the running product**.

---

## Appendix A — `docs/missions/` folder index (61 folders, 488 files)

Each folder typically contains SCOPE, REPORT, TEST, SIGNOFF markdown files documenting build results for that mission. Folders:

`mission_07` through `mission_37` (31 foundation missions), `phase_1` through `phase_15` (15 phase packages), `p1_online_request_intake` through `p10_release_hardening_navigation_rc` (10 operational missions).

**Collective intent:** Incrementally build auth, entities, JobCard, operations, parts, finance, QC, UX, packaging — culminating in P1–P10 operational workflow reports marked complete in master prompt.

---

## Appendix B — `docs/implementation/` (175 files)

Wave-based implementation specs (e.g. `wave_9_executive_readiness`, readonly page specs). **Type:** PLAN / BLUEPRINT. **Area:** cross-cutting. **Relevance:** supporting material for phases 16–23 and executive readiness — not primary V1 RC reception scope.

---

## Appendix C — Document inventory statistic summary

| Category | File count (approx.) |
|----------|---------------------:|
| AUDIT_REPORT | 95 |
| SCOPE | 45 |
| PHASE_REPORT / MISSION_REPORT | 550 |
| MASTER_PROMPT / BLUEPRINT | 40 |
| MANIFEST / RELEASE_NOTE | 45 |
| SIGNOFF / TEST_REPORT | 80 |
| PLAN / USER_GUIDE | 374 |
| **Total** | **1,229** |

*(Counts derived from folder classification rules applied to all 1,229 files; individual row expansion for missions/implementation available on request in a machine-readable CSV — not generated in this freeze phase to avoid creating additional files.)*

---

**Commit Eligibility:** `NOT_ELIGIBLE_DOCS_INVENTORY_ONLY`

**End of report**
