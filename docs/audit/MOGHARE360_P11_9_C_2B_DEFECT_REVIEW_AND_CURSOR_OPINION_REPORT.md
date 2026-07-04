# MOGHARE360 P11.9-C-2B — Defect Review + Cursor Opinion Report

**Phase:** P11.9-C-2B-RECEPTION-DEFECT-REVIEW  
**Mode:** Report only — no code, SQL, Auth, CSS, or commit changes  
**Date:** 2026-07-04  
**Product:** MOGHARE360 V1 RC  
**Subject:** Comprehensive defect review of Reception Workbench / Intake Completion before any further fix or C-2C

---

## 1. Executive Summary

The owner’s rejection of P11.9-C-2B is **substantiated**. The current 14-file uncommitted reception bundle delivers a **read-only intake shell** and a **partially aligned workbench landing**, but it is **not commit-ready**, **not UAT-ready**, and **not C-2C-ready**.

**What exists and has value:**
- Two-card landing (`پذیرش` + `پروفایل پرسنلی`) on `erp-reception-workbench.php`
- Reception process hub cards (پذیرش موقت / پذیرش / QC / ترخیص) as navigation structure
- Intake aggregation helper with payload decode, gate logic skeleton, service taxonomy display
- Intake-first CTA on online request detail (post REWORK-A)
- Runtime fatal fix in source for `m360_rw_build_gate()` 9-argument call (post REWORK-A)

**What remains broken or incomplete:**
- **Browser validation is still unproven** for the operator; prior PASS reports were undermined by a P0 fatal that static tests missed
- **No operational write path** for intake completion, service classification, expert review, or field updates
- **UX is split** between green luxury workbench/intake and legacy blue/white table admin pages
- **Intake page is overloaded** — one long scroll with many «نیازمند مسیر تکمیل در فاز بعد» placeholders
- **Mock UX routes leak** into walk-in reception as if operational
- **Process model is label-deep, not workflow-deep** — temp→full→convert transitions are not operable end-to-end

**Cursor verdict:** **STOP_AND_REWORK** — execute **P11.9-C-2B-REWORK-FINAL** (Hybrid Option D) before commit and before C-2C.

**Browser Validation:** PENDING OPERATOR — this report does not claim browser PASS.

---

## 2. Technical Defects

| ID | Defect | Evidence |
|----|--------|----------|
| T-01 | **P0 fatal (historical)** — `m360_rw_build_gate()` called with 8 args in `m360_rw_build_intake_file()` line ~558 | UAT browser error; UAT rejection report §2; fixed in REWORK-A source but operator browser not re-confirmed |
| T-02 | **Test suite falsely implied production readiness** — static string tests passed while intake bootstrap fatally failed | UAT rejection §3; `test-p11-9-c-2b-intake-completion-shell.php` never called `m360_rw_build_intake_file()` until REWORK-A smoke |
| T-03 | **No HTTP-level runtime test** — REWORK-A smoke calls PHP functions only, not `erp-reception-intake-file.php` via HTTP | `test-p11-9-c-2b-rework-a-runtime-intake-smoke.php` — no curl/HTTP 200 assertion |
| T-04 | **Fragile 9-parameter gate signature** — any missed call site causes immediate fatal | Gate expanded in FIX-A; regression already occurred once |
| T-05 | **Monolithic helper (~970 lines)** — reads, gate, KPI, hub cards, taxonomy in one file | `m360-reception-workbench-helper.php` — high coupling, hard to test in isolation |
| T-06 | **Dead code** — `m360_rw_workbench_operational_cards()` defined but never used | Grep shows definition only; workbench uses `m360_rw_reception_process_hub_cards()` instead |
| T-07 | **Likely intake fetch bug** — `m360_rw_fetch_intake_row()` queries `intake_id = ?` with `$customerId` | Line ~332–335 uses customer_id as intake_id parameter name mismatch |
| T-08 | **Misleading KPI** — `ready_convert` counts DB status ACCEPTED/UNDER_REVIEW, not gate `ready_convert` | `m360_rw_workbench_kpis()` SQL ~501–507 — diverges from gate semantics |
| T-09 | **Repo vs XAMPP divergence risk** — operator may run stale helper while pages updated | UAT rejection §3.4; uncommitted bundle not deployed uniformly |
| T-10 | **No automated PHP lint in full C-2B gate** — only partial files linted in REWORK-A smoke | Missing lint for workbench.php, online-requests.php in reception gate |

---

## 3. Process Defects

| ID | Defect | Evidence |
|----|--------|----------|
| P-01 | **Temporary vs full reception is display-only** — gate sets `reception_mode` but no operational promote/demote workflow | Gate labels in intake; no staff action to move temp→full except implicit field completion (which has no write) |
| P-02 | **Intake completion is read-only shell** — fields cannot be completed by reception | Placeholder `M360_RW_SERVICE_CLASS_WRITE_PLACEHOLDER`; gate missing items append «نیازمند مسیر تکمیل در فاز بعد» |
| P-03 | **Service classification not persistable** — blocks fault path and convert gate | `m360_rw_parse_service_classification()` reads payload keys; no POST handler to write them |
| P-04 | **Expert / diagnostic review not routed** — placeholder text only | Intake line ~115: «ارجاع کارشناسی / عیب‌یابی اولیه — ثبت عملیاتی در فاز بعد» |
| P-05 | **Accept (پذیرش درخواست) removed from detail but not clearly relocated** — intake shows reject/under_review in temp, convert when ready; no explicit accept on intake | Detail demoted; intake temp section has reject + request-info, not accept |
| P-06 | **Walk-in reception has no real operational path** — links to mock UX pages | `erp-reception-workbench.php?section=walkin` → `erp-jobcard-create-ux.php`, `erp-customer-vehicle-create-ux.php` |
| P-07 | **QC hub card inconsistent** — marked placeholder but links to `erp-qc-board.php` | `m360_rw_reception_process_hub_cards()` QC entry |
| P-08 | **Convert correctly gated in logic** but **cannot be reached operationally** for real cases until writes exist | Gate requires payload fields reception cannot set without C-2C write actions |
| P-09 | **Temp rejection allowed on intake** — aligned with owner model | Intake temp section POST reject — **Partial PASS** |
| P-10 | **Tracking «پرونده‌های در جریان» points to JobCard list** — not intake-centric tracking | Hub sub-link → `erp-reception-jobcards.php` |

---

## 4. UX/UI Defects

| ID | Defect | Evidence |
|----|--------|----------|
| U-01 | **Visual system split** — green luxury (`moghare360-v1-luxury-ui.css`) vs legacy blue/white (`moghare360-soft-run-release.css`) | Workbench/intake vs online-requests/detail |
| U-02 | **Intake page overloaded** — 10+ stacked panels on one scroll | `erp-reception-intake-file.php` — customer, vehicle, complaint, service class, payload, intake ERP, photos, contracts, checklist, convert |
| U-03 | **Weak information hierarchy** — gate status competes with full field dump | Gate panel at top but checklist repeated at bottom |
| U-04 | **Placeholder fatigue** — many fields show em-dash or phase-next warnings | Gate `phase_next` items in missing list |
| U-05 | **Mock UX presented as walk-in actions** — buttons look operational | Walk-in section «راهنمای UX پذیرش», «ثبت مشتری/خودرو» |
| U-06 | **Online list remains table-admin aesthetic** — not aligned with workbench brand | `erp-reception-online-requests.php` inline styles, `.p1-req-table` |
| U-07 | **Detail page hybrid styling** — REWORK-A CTA added but page still legacy shell | `erp-reception-online-request-detail.php` — w1c-wrap + new green CTA block |
| U-08 | **No step-by-step intake wizard** — owner process implies sequential completion | Single page all-in-one |
| U-09 | **Hub cards for QC/ترخیص show placeholder text** — acceptable for V1 shell but visually inconsistent with active cards | Process hub cards |
| U-10 | **Footer nav duplication** — multiple entry points without clear primary flow | Intake footer + detail nav + list nav all cross-link |

---

## 5. Service Classification Defects

| ID | Defect | Evidence |
|----|--------|----------|
| S-01 | **Taxonomy displayed correctly** — owner A/B/C structure present | `m360_rw_service_classification_taxonomy()` |
| S-02 | **Not write-enabled** — read-only display + placeholder | `M360_RW_SERVICE_CLASS_WRITE_PLACEHOLDER` |
| S-03 | **Not persisted** — reads `reception_service_*` keys from payload if present; no save | `m360_rw_parse_service_classification()` |
| S-04 | **No multi-select UI** — taxonomy listed as static HTML lists | Intake service class panel |
| S-05 | **Customer `request_type` separated in logic** — mismatch warning shown | `customer_mismatch` flag when customer type set but reception class empty — **good** |
| S-06 | **Risk: staff may confuse customer request_type with reception classification** — mitigated by labels but not by UI enforcement | List/detail still show «نوع درخواست» from customer |
| S-07 | **No reporting foundation** — cannot aggregate service demand/revenue without persisted reception classification | Managerial requirement unmet until C-2C+ |

---

## 6. Data / Payload Defects

### 6.1 Fields read from `online_request` columns

| Field | Used in |
|-------|---------|
| `online_request_id`, `request_status`, `created_at` | All pages |
| `mobile`, `customer_name`, `vehicle_plate` | List, detail, intake, gate |
| `customer_id`, `vehicle_id` | Intake → ERP customer/vehicle fetch |
| `service_note` | Gate complaint check, intake display |
| `request_type`, `visit_date`, `source_channel` | Detail, payload fallback |
| `request_payload_json` | Decode for all extended fields |
| `converted_jobcard_id` | Gate converted check |

### 6.2 Fields read from `request_payload_json`

Known mapped keys via `m360_rw_payload_label_map()`: vin, brand, model, odometer_km, fuel_level, belongings, visible_damage, diagnostic, cost_agreement, otp_verified, plate_parts, etc.

**Reception-internal keys (read if present, no write):**
- `reception_service_primary`, `reception_service_diag_sub`, `reception_service_periodic`, `reception_service_trade`
- `fault_service_path_clear`, `reception_final_confirmation`

### 6.3 Missing due to no write path

| Missing operational data | Impact |
|--------------------------|--------|
| Reception service classification | Blocks fault path / temp exit |
| VIN, mileage, fuel, damage updates by staff | Gate soft items stay open |
| Expert review routing record | Temp workflow incomplete |
| Final reception confirmation | Blocks `ready_convert` |
| Cost agreement / contract linkage from intake | Gate items remain phase-next |

### 6.4 Payload-update feasibility (no schema change)

**Can later be stored in `request_payload_json` without DB migration:**
- Service classification codes (multi-select array)
- `fault_service_path_clear` flag
- Field completions (vin, odometer, fuel, etc.)
- `reception_final_confirmation`
- Expert review status notes

**Should later move to structured tables if reporting grows:**
- Service classification dimensions (for OLAP / revenue by line)
- Expert review assignments
- Reception audit trail per field change

---

## 7. Action Placement Defects

| Action | Current (post REWORK-A) | Problem |
|--------|-------------------------|---------|
| تکمیل پرونده پذیرش | Detail CTA + list button + nav | **Correct primary path** |
| رد درخواست | Intake temp section only | **Correct** for temp |
| درخواست تکمیل اطلاعات | Intake temp (`under_review`) | **Correct** location |
| تبدیل به کارت کار | Intake when `can_show_convert` | **Correct** gating; rarely reachable without writes |
| پذیرش درخواست | **Removed from detail**; not on intake | **Gap** — accept path unclear |
| علامت‌گذاری در حال بررسی | Removed from detail; partial on intake as «تکمیل اطلاعات» | **Partial** |
| ارجاع کارشناسی | Placeholder only | **Missing** |
| Convert from raw detail | Removed | **Fixed** |

---

## 8. Test Coverage Defects

| Test file | What it proves | What it misses |
|-----------|----------------|----------------|
| `test-p11-9-c-2b-reception-workbench.php` | Strings + JSON decode unit | HTTP, intake bootstrap, browser |
| `test-p11-9-c-2b-intake-completion-shell.php` | Strings + direct `m360_rw_build_gate()` | Was missing `m360_rw_build_intake_file()` until REWORK-A |
| `test-p11-9-c-2b-fix-a-process-correction.php` | Taxonomy strings + gate unit cases | HTTP, browser, write flows |
| `test-p11-9-c-2b-scope-security.php` | Scope boundaries | Runtime |
| `test-p11-9-c-2b-rework-a-runtime-intake-smoke.php` | `m360_rw_build_intake_file()` + DB 18/20 | **No HTTP GET**; no rendered HTML assert |
| `test-v1-production-signoff.php` | Unrelated V1 signoff | Reception routes |

**Core gap:** Tests can report PASS while browser still fails (auth redirect, stale deploy, CSS 404, session). **Operator browser is mandatory** for commit gate.

---

## 9. Defect Matrix

| Defect | Type | Current evidence | Business impact | Severity | Required correction | Can fix without DB? | Must fix before commit? | Must fix before C-2C? |
|--------|------|------------------|-----------------|----------|---------------------|---------------------|-------------------------|----------------------|
| Gate 8-arg fatal (historical) | Technical | UAT browser fatal; fixed in source REWORK-A | Intake unusable | P0 | Verify 9-arg all sites + browser | Yes | Yes | Yes |
| No HTTP smoke test | Test | REWORK-A CLI only | False PASS risk | P0 | Add HTTP 200 smoke for intake 18/20 | Yes | Yes | Yes |
| Browser not validated | Test | REWORK-A report PENDING OPERATOR | Commit blocked | P0 | Operator browser checklist | Yes | Yes | Yes |
| Intake read-only / no writes | Process | Placeholder text throughout | Reception cannot complete files | P0 | C-2C write actions (bounded) | Mostly yes (payload JSON) | No (defer writes to C-2C) | Yes |
| Service classification not saved | Data / Process | Display only | No managerial reporting | P0 | C-2C persist handler | Yes (payload) | No | Yes |
| Mock UX in walk-in route | UX / Process | walkin section links | Staff confusion | P1 | Remove or label mock; defer real walk-in | Yes | Yes | No |
| Visual split luxury vs legacy | UX | Two CSS systems | Unacceptable near-release UX | P1 | Minimum skin alignment on list/detail | Yes | Yes | No |
| Overloaded intake page | UX | Single long page | Operator error, fatigue | P1 | Tab/step layout minimum | Yes | Yes | No |
| Expert review missing | Process | Placeholder | Temp workflow incomplete | P1 | C-2C or REWORK-FINAL placeholder route | Yes | Partial | Yes |
| Accept action placement gap | Action | Not on detail or intake | Workflow hole | P1 | Define accept on intake when gate allows | Yes | Yes | Yes |
| KPI ready_convert mismatch | Technical | SQL status vs gate | Wrong dashboard counts | P2 | Align KPI with gate or rename | Yes | No | No |
| Dead operational_cards function | Technical | Unused function | Maintainability | P2 | Remove or wire up | Yes | No | No |
| Intake fetch customerId bug | Technical | Wrong column in query | Wrong intake row | P2 | Fix query | Yes | Yes | No |
| QC hub placeholder inconsistency | UX | Link + placeholder | Broken expectation | P3 | Disable link or remove placeholder flag | Yes | No | No |

---

## 10. File Decision Matrix (14 uncommitted files)

| File | Current purpose | Problem | Decision | Reason | Required action | Commit eligibility |
|------|-----------------|---------|----------|--------|-----------------|-------------------|
| `public_html/erp-reception-workbench.php` | Landing + profile + reception hub | Mock UX links in walk-in; unused operational card data in helper | **Keep but modify** | Core navigation correct | Remove/mock-label UX links; browser validate | **Not yet** |
| `public_html/erp-reception-intake-file.php` | Intake completion shell | Overloaded; read-only; temp actions only | **Keep but modify** | Core deliverable | Split UX sections; keep gate-first; browser validate | **Not yet** |
| `public_html/includes/m360-reception-workbench-helper.php` | Aggregation + gate + taxonomy | Monolith; intake fetch bug; KPI mismatch | **Keep but modify** | Foundation salvageable | Fix fetch; stabilize gate; optional split | **Not yet** |
| `public_html/erp-reception-online-requests.php` | Online list | Legacy UX; dual CTA ok | **Keep but modify** | Operational entry | Align CSS minimally; keep intake link | **Not yet** |
| `public_html/erp-reception-online-request-detail.php` | Request detail | Legacy shell; CTA fixed in REWORK-A | **Keep but modify** | Action placement improved | Browser validate; optional CSS align | **Not yet** |
| `public_html/erp-staff-home.php` | Staff dashboard | Small RECEPTION hub addition | **Keep as-is** | Minimal correct entry | None critical | **Defer** until bundle ready |
| `public_html/assets/css/moghare360-v1-luxury-ui.css` | Workbench/intake styles | Does not cover list/detail | **Keep but modify** | Shared design tokens | Extend or bridge legacy pages | **Not yet** |
| `docs/audit/MOGHARE360_P11_9_C_2B_RECEPTION_WORKBENCH_REPORT.md` | Implementation report | Claimed complete before UAT | **Keep** | Audit trail | Annotate superseded by rejection | **Yes (docs)** |
| `docs/audit/MOGHARE360_P11_9_C_2B_RECEPTION_WORKBENCH_SCOPE_REPORT.md` | Scope gate | Still valid | **Keep** | Scope reference | None | **Yes (docs)** |
| `docs/audit/MOGHARE360_P11_9_C_2B_UAT_REJECTION_REPORT.md` | UAT rejection | Accurate | **Keep** | Source of truth | None | **Yes (docs)** |
| `docs/audit/MOGHARE360_P11_9_C_2B_REWORK_A_REPORT.md` | REWORK-A fixes | Browser PENDING | **Keep** | Traceability | Update after operator browser | **Yes (docs)** |
| `tools/test-p11-9-c-2b-reception-workbench.php` | Static workbench test | Shallow | **Keep but modify** | Baseline | Add negative regressions | **With code** |
| `tools/test-p11-9-c-2b-intake-completion-shell.php` | Static intake test | Missed fatal | **Keep but modify** | Must call intake builder | Merge into runtime gate | **With code** |
| `tools/test-p11-9-c-2b-fix-a-process-correction.php` | Process strings | Static | **Keep but modify** | Taxonomy guard | Keep | **With code** |
| `tools/test-p11-9-c-2b-scope-security.php` | Scope guard | Valid | **Keep as-is** | Boundary check | None | **With code** |
| `tools/test-p11-9-c-2b-rework-a-runtime-intake-smoke.php` | Runtime smoke | No HTTP | **Keep but modify** | Best current runtime test | Add HTTP layer | **With code** |

**Note:** Files listed in the prompt but **not present** as separate pages (`erp-reception-personnel-hub.php`, `process-hub.php`, `temporary.php`, etc.) — functionality is **embedded** in `erp-reception-workbench.php` + helper hub cards. **Decision:** do not create duplicate hub files; refine existing workbench sections.

---

## 11. Owner Process Compliance Matrix

| Owner requirement | Current implementation | Pass / Partial / Fail | Evidence | Required correction |
|-------------------|------------------------|----------------------|----------|---------------------|
| Landing: only پذیرش + پروفایل پرسنلی | Two-card landing grid | **Pass** | `erp-reception-workbench.php` landing | Minor footer noise only |
| Profile: 4 HR shortcuts | Profile section with 4 cards | **Partial** | Placeholders when HR files missing | Accept for V1 shell |
| Reception hub: temp / پذیرش / QC / ترخیص | 4 hub cards in `?section=reception` | **Partial** | QC/ترخیص placeholder; temp links to filtered list | Wire temp list to gate filter later |
| Full reception subs: walk-in, online/intake, tracking | Sub-links under پذیرش card | **Partial** | Walk-in mock; tracking → jobcards | Real walk-in deferred; intake link ok |
| Temp reception: complete file, unclear fault ok | Gate statuses `temporary_reception`, `complete_unclear_fault` | **Partial** | Labels yes; writes no | C-2C writes |
| Temp: reject allowed | Intake temp POST reject | **Pass** | Intake temp section | Browser validate |
| Temp: request info allowed | Intake `under_review` POST | **Pass** | Intake temp section | Browser validate |
| Temp: expert review allowed | Placeholder text only | **Fail** | No route/action | C-2C or REWORK-FINAL stub |
| Temp: convert blocked | Gate + UI warning | **Pass** | Logic + intake message | — |
| Full reception: fault path clear | Gate `ready_full_reception` / `ready_convert` | **Partial** | Logic exists; unreachable without writes | C-2C |
| Convert: not from raw detail | Detail demoted to intake CTA | **Pass** | REWORK-A detail | Browser validate |
| Convert: behind gate, no OTP bypass | Uses existing accept.php + OTP check in gate | **Pass** | Gate `otp_required` | — |
| Service classification by receptionist | Display + taxonomy | **Partial** | No write/persist | C-2C |
| Service taxonomy A/B/C exact | Matches owner list | **Pass** | Helper taxonomy | — |
| Intake-first primary action | Detail + list intake links | **Pass** | REWORK-A | Browser validate |

---

## 12. Runtime Test Coverage Matrix

| Page / function | Current test coverage | Missing runtime coverage | Required test | Browser URL required? |
|-----------------|----------------------|--------------------------|---------------|----------------------|
| `m360_rw_build_intake_file()` | REWORK-A smoke | — | Keep in gate | No |
| `m360_rw_build_gate()` 9-arg | Unit tests + smoke | Regression guard on all call sites | Regex + integration | No |
| `erp-reception-intake-file.php` HTTP | **None** | Full page render | HTTP GET 200, no fatal string | **Yes** — `?online_request_id=18`, `20` |
| `erp-reception-workbench.php` HTTP | **None** | Auth + render | HTTP smoke after staff session | **Yes** — landing + `?section=reception` |
| `erp-reception-online-request-detail.php` | String checks REWORK-A | Rendered HTML | No accept/reject/convert buttons | **Yes** — sample request_id |
| `erp-reception-online-requests.php` | String link check | Render table | HTTP 200 | **Yes** |
| `erp-staff-home.php` RECEPTION hub | String check | — | Optional HTTP | Optional |
| Walk-in mock link safety | **None** | Assert mock labeled/de-linked | Static or HTTP | Yes |

---

## 13. UX Rework Matrix

| Page | Current UX problem | Required UX direction | Minimum acceptable fix | Later polish backlog |
|------|-------------------|----------------------|------------------------|---------------------|
| Workbench landing | Good structure | Maintain two-card clarity | Keep as-is | KPI accuracy |
| Workbench reception hub | Mixed active/placeholder cards | Clear visual disabled state for QC/ترخیص | Stronger placeholder styling | Full QC/ترخیص modules |
| Walk-in section | Mock links look real | De-operationalize mocks | Label «UX نمایشی — غیرعملیاتی» + remove primary styling | Real walk-in intake |
| Intake file | Overloaded scroll | Step/tabs: Gate → Customer/Vehicle → Service class → Docs → Actions | Collapse sections; sticky gate summary | Wizard flow |
| Online list | Legacy table | Align colors/typography with luxury UI | Shared header + button styles | Full card list view |
| Online detail | Legacy + green CTA patch | Unified shell | Apply luxury CSS or shared banner | Full redesign |
| Service class panel | Static taxonomy list | Interactive multi-select (C-2C) | Keep read-only but clearer «ثبت نشده» callout | Reporting dashboards |

---

## 14. Action Placement Matrix

| Action | Current location | Correct location | Allowed state | Blocked state | Required UI behavior |
|--------|------------------|------------------|---------------|---------------|----------------------|
| تکمیل پرونده پذیرش | List, detail, nav | **Primary everywhere** | Non-converted, non-rejected | Converted/rejected | Prominent CTA |
| رد درخواست | Intake temp | Intake temp/full review | Temp + gate allows temp actions | Converted/rejected | Confirm dialog (exists) |
| درخواست تکمیل اطلاعات | Intake temp | Intake temp | Temp | Converted/rejected | Secondary button |
| ارجاع کارشناسی | Nowhere | Intake temp section | Temp, fault unclear | After path clear | Button + placeholder → C-2C |
| پذیرش درخواست (accept) | Removed | Intake when `ready_full_reception`+ | Full gate passed soft items | Temp, incomplete, OTP | Hidden until gate allows |
| تبدیل به کارت کار | Intake when `ready_convert` | Intake only | `ready_convert` | Temp, OTP, incomplete | Hidden + explanation otherwise |
| علامت‌گذاری در حال بررسی | Removed from detail | Merged into «تکمیل اطلاعات» | Temp | — | OK as merge |

---

## 15. Service Classification Matrix

| Classification item | Current display | Write/persist | Required C-2B rework | Required C-2C write | Reporting need |
|--------------------|-----------------|---------------|----------------------|---------------------|----------------|
| کارشناسی و عیب‌یابی (primary) | Taxonomy label | Read payload if set | Keep display | Primary select POST | Demand by category |
| موتور و گیربکس | Sub list | Read array if set | Keep display | Multi-select POST | Sub-line analysis |
| برق و باتری | Sub list | Read array if set | Keep display | Multi-select POST | Sub-line analysis |
| زیروبند و تعلیق | Sub list | Read array if set | Keep display | Multi-select POST | Sub-line analysis |
| مبلمان داخلی | Sub list | Read array if set | Keep display | Multi-select POST | Sub-line analysis |
| خدمات بدنه | Sub list | Read array if set | Keep display | Multi-select POST | Sub-line analysis |
| آپشن | Sub list | Read array if set | Keep display | Multi-select POST | Sub-line analysis |
| سرویس‌های دوره‌ای | Taxonomy label | Read if set | Keep display | Toggle/select POST | Periodic service volume |
| کارشناسی خرید و فروش | Taxonomy label | Read if set | Keep display | Toggle/select POST | Trade-in/out metrics |
| `fault_service_path_clear` | Gate internal | Read flag only | — | Set when class complete | Gate transition |
| Customer `request_type` | Shown separately | N/A (customer) | Keep mismatch warning | Never write as class | Separate column in reports |

---

## 16. Cursor Technical Opinion

**Is C-2B code salvageable?** **Yes — as a foundation, not as a finished module.** The helper’s read/decode/gate/taxonomy structure is directionally correct and reuses P1/P1.5 responsibly. The pages are thin shells — appropriate for a phased rollout — but the **P0 fatal and test false-PASS** destroyed trust and must never recur.

**Patch helper or simplify?** **Patch and thin-split, not rewrite.** Keep `m360_rw_build_gate()` but add: (1) a single factory for empty gate calls, (2) fix `m360_rw_fetch_intake_row()`, (3) remove dead `m360_rw_workbench_operational_cards()` or wire it intentionally, (4) optional file split only after REWORK-FINAL stabilizes.

**Is the 9-parameter signature stable?** **Stable if disciplined.** The regression proved that every new parameter needs a enforced test calling `m360_rw_build_intake_file()` — not just direct gate unit tests.

**Are tests trustworthy?** **Not yet for release signoff.** REWORK-A improved runtime coverage but still lacks HTTP smoke. **Trust hierarchy:** operator browser > HTTP smoke > `m360_rw_build_intake_file()` > string tests.

**What must change before commit?**
1. Operator browser PASS on intake 18/20 and detail action placement
2. HTTP-level smoke in CI/tools
3. Mock UX de-linked or clearly non-operational
4. Minimum UX alignment on list/detail
5. Documented C-2C boundary (writes explicitly deferred)

---

## 17. Cursor Process Opinion

The implementation **partially reflects** the workshop reception process at the **navigation and gate-label level**, but **not at the operational level**. Staff can see what is missing but cannot fix it. Temp reception is the most honest part (reject + request info on intake). Full reception and convert are **theoretical** until C-2C writes exist.

**Minimum rework for operational coherence (before C-2C):**
- Intake-first flow enforced (done in REWORK-A source)
- Browser-proven runtime
- Clear «read-only until C-2C» messaging on intake (reduce placeholder confusion)
- Remove mock routes from primary walk-in path

**What must wait for C-2C:**
- Service classification multi-select save (payload JSON)
- Field completion writes (VIN, mileage, etc.)
- Expert review routing record
- Accept action when full gate satisfied
- Any reporting aggregation

---

## 18. Cursor UX Opinion

**Is current UX acceptable for near-release V1 RC?** **No.** The split between luxury workbench and legacy admin pages feels like two products. The intake page exposes internal phase language («فاز بعد») to end users — unacceptable for production reception staff.

**Immediate redesign needed:** online list, online detail (minimum skin), intake information architecture (collapse/steps).

**Can remain as shell:** workbench landing, profile section, QC/ترخیص placeholders with clear disabled visual treatment.

---

## 19. Cursor File Disposition Opinion

| Disposition | Files |
|-------------|-------|
| **Keep with light touch** | `erp-staff-home.php`, scope tests, audit docs |
| **Keep but heavily modify** | workbench.php, intake-file.php, helper, online-requests.php, online-detail.php, luxury CSS |
| **Remove from operational navigation** | Mock links: `erp-jobcard-create-ux.php?role=reception`, `erp-customer-vehicle-create-ux.php?role=reception` from walk-in primary actions |
| **Docs/tests can remain** | All `docs/audit/MOGHARE360_P11_9_C_2B*.md`, all `tools/test-p11-9-c-2b-*.php` (enhanced) |

**Do not revert entirely** — reverting would lose gate taxonomy, intake aggregation, and REWORK-A fixes. **Do not commit as-is** — owner rejection is correct.

---

## 20. Cursor Managerial Recommendation

**Chosen option: D — Hybrid: keep useful foundations, rework runtime/process/UX before commit**

| Option | Assessment |
|--------|------------|
| A Patch fatal only | **Insufficient** — UX/process gaps remain; owner rejected whole state |
| B Rework C-2B before commit | **Correct direction** — aligns with D |
| C Revert and restart | **Too destructive** — foundation is usable |
| **D Hybrid** | **Recommended** — keep helper/pages/tests/docs; complete REWORK-FINAL gate |

**Justification:** The team already invested in the correct owner navigation model and gate semantics. A full revert wastes that work. A fatal-only patch repeats the UAT failure pattern (green tests, red browser). Hybrid rework closes P0 runtime, enforces browser gate, improves minimum UX, and draws a hard C-2C line for writes — matching owner constraints (no SQL/Auth/workflow changes in rework shell phase).

---

## 21. Proposed Final Rework Plan — P11.9-C-2B-REWORK-FINAL

### 21.1 Files to modify

| File | Changes |
|------|---------|
| `public_html/includes/m360-reception-workbench-helper.php` | Fix intake fetch; empty-gate factory; KPI label fix or rename; remove/wire dead cards |
| `public_html/erp-reception-intake-file.php` | Section collapse/sticky gate; clearer read-only vs C-2C messaging; accept button placement when gate allows (read-only label until C-2C if no write) |
| `public_html/erp-reception-workbench.php` | De-operationalize mock UX links; QC link consistency |
| `public_html/erp-reception-online-requests.php` | Minimum luxury CSS alignment |
| `public_html/erp-reception-online-request-detail.php` | CSS alignment; verify CTA-only |
| `public_html/assets/css/moghare360-v1-luxury-ui.css` | Shared components for list/detail bridge |
| `tools/test-p11-9-c-2b-rework-a-runtime-intake-smoke.php` | Add HTTP GET assertions |
| `tools/test-p11-9-c-2b-intake-completion-shell.php` | Require `m360_rw_build_intake_file()` |

### 21.2 Files to keep (foundation)

- All workbench/intake/helper pages (modified, not removed)
- All audit docs
- All C-2B test tools (enhanced)

### 21.3 Remove from operational navigation

- Primary walk-in buttons → `erp-jobcard-create-ux.php?role=reception`
- Primary walk-in buttons → `erp-customer-vehicle-create-ux.php?role=reception`
- Do **not** delete those files — demote to «UX preview only» if linked at all

### 21.4 Runtime fatal fix

- **Status:** Applied in REWORK-A source (`9`-arg empty gate)
- **REWORK-FINAL:** Operator browser confirmation + HTTP smoke + regression test in all PR gates

### 21.5 Action placement fix

- **Status:** Detail demoted in REWORK-A
- **REWORK-FINAL:** Browser verify; define accept on intake for full gate (display-only until C-2C if no POST)

### 21.6 UX minimum acceptable rework

- Unified header/banner on list + detail
- Intake: sticky gate bar + collapsible sections
- Replace «فاز بعد» user-facing text with «نیازمند ثبت توسط پذیرشگر (فاز تکمیل عملیات)»
- Mock routes visually distinct

### 21.7 Browser URLs that must pass

| URL | Criterion |
|-----|-----------|
| `erp-reception-workbench.php` | Landing two cards render |
| `erp-reception-workbench.php?section=reception` | Hub 4 cards render |
| `erp-reception-intake-file.php?online_request_id=18` | HTTP 200, no fatal, gate visible |
| `erp-reception-intake-file.php?online_request_id=20` | HTTP 200, no fatal, gate visible |
| `erp-reception-online-request-detail.php?request_id=18` | Only intake CTA, no sensitive POST buttons |
| `erp-reception-online-requests.php` | List renders; intake link works |

### 21.8 Tests that must run

1. `php -l` on all modified public_html PHP
2. `test-p11-9-c-2b-rework-a-runtime-intake-smoke.php` (with HTTP)
3. `test-p11-9-c-2b-intake-completion-shell.php`
4. `test-p11-9-c-2b-fix-a-process-correction.php`
5. `test-p11-9-c-2b-scope-security.php`
6. `test-p11-9-c-2b-reception-workbench.php`
7. Operator browser checklist (mandatory, not substitutable)

### 21.9 Owner acceptance checklist

- [ ] Intake 18/20 open in browser without fatal
- [ ] Detail page shows intake-first only
- [ ] Temp reject works from intake (existing handler)
- [ ] Convert not visible on detail; blocked on intake when gate says temp
- [ ] Service taxonomy visible; staff understands not yet savable (until C-2C)
- [ ] Walk-in does not present mock UX as production
- [ ] Visual split reduced to acceptable minimum
- [ ] No Auth/SQL/workflow/OTP changes in rework shell phase

### 21.10 Commit criteria

- All automated tests PASS including HTTP smoke
- **Browser Validation: OPERATOR PASS** documented (not PENDING)
- No P0/P1 defects open from §9 matrix except those explicitly deferred to C-2C with owner sign-off
- Audit report updated with honest browser status

### 21.11 Explicit C-2C boundary

**C-2B / REWORK-FINAL (allowed):** read shells, gate display, navigation, action placement, CSS alignment, runtime tests, de-mock navigation

**C-2C (next phase, not in rework shell):**
- POST write actions for service classification (multi-select → payload JSON)
- Field completion saves
- Expert review routing persistence
- Accept action POST when gate allows
- Optional: gate-driven KPI queries

**Forbidden in both without explicit scope:** SQL migrations, Auth changes, auto JobCard creation, fake OTP, P12

---

## 22. Stop / Continue Decision

| Decision | Status |
|----------|--------|
| PATCH_ONLY_ALLOWED | **No** — insufficient |
| REVERT_REQUIRED | **No** — foundation salvageable |
| READY_FOR_C2C | **No** — process/UX/runtime/browser gate not met |
| **STOP_AND_REWORK** | **Yes — active recommendation** |

Proceed with **P11.9-C-2B-REWORK-FINAL** after owner acknowledges this defect review. Do **not** start C-2C until REWORK-FINAL commit criteria and operator browser PASS are satisfied.

---

P11.9-C-2B-DEFECT-REVIEW documents the complete technical, UX, process, data, action placement, test coverage, and file-disposition defects of the current reception implementation and records Cursor’s own technical and managerial opinion before any corrective implementation, without changing code, SQL, Auth/Login, permissions, roles, workflow, users, JobCards, OTP, private files, secrets, or P12 scope.
