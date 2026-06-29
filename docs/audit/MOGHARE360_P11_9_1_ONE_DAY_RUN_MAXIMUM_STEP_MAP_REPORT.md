# MOGHARE360 P11.9-1 — One-Day Run Maximum Step Map / Operator Decision Report

**Phase:** P11.9-1  
**Mode:** REPORT ONLY  
**Date:** 2026-06-26  
**Product:** MOGHARE360 V1 RC  
**Source reports:** P11.9-0, P11.8-C, P11.8-B-A, P11.8-A, P11.7.1-A  
**Security scope:** No code, SQL, Auth, permissions, roles, route maps, users, demo data, workflow actions, OTP config, or P12 scope changed.

---

## 1. Executive Summary

This report converts P11.9-0 readiness findings into a **maximum-step operator map** for a controlled One-Day Run dry run. It defines **102 discrete operational steps** across 9 roles plus pre/post-run operator controls, with explicit decision points for demo data, OTP, runtime-hold pages, and navigation safety.

**Overall dry-run posture:** **CONDITIONAL GO** — execute only after **P11.9-A Dry Run Pack** prerequisites (staff users + fresh `M360-DEMO` JobCard + operator briefing). UI/navigation is **READY/PARTIAL**; environment prep is **UNKNOWN** until operator verifies live DB.

| Decision area | Report decision |
|---------------|-----------------|
| Staff users | **6 minimum staff roles + OWNER oversight** must exist before run — prerequisite **P11.9-A** |
| Demo JobCard | **Fresh `M360-DEMO-*` JobCard** — do **not** rely on JobCard ID 1 |
| OTP/SMS | **Defer customer OTP legs** for staff-centric dry run; record deferral in operator log |
| Part-use page | **SKIP UI** — use `erp-part-reserve.php`; manual observation backlog |
| Payment tracking | **SKIP UI** — verify via estimate + final invoice/settlement only |
| Action endpoints | **Not blockers** — not linked; actions only from detail forms |
| P1/P1.5 shell gap | **WARNING only** — use with operator guide |
| Route Map | **USE** — **نمای عملیاتی** only as navigation reference |

---

## 2. Owner Requirement Interpretation

The owner’s 12-step workshop flow is expanded here into **102 maximum steps** where each row is one human action, verification, or explicit operator decision. Steps are ordered logically but **branches exist** (online intake vs walk-in; OTP deferred vs live). Unknown live DB state is marked **NEEDS OPERATOR CHECK**.

Dry run rules inherited from P11.9-0:

- Real staff logins (not shared owner login for line roles)
- One traceable sample JobCard (`M360-DEMO` prefix)
- Board → detail → form action pattern only
- No impersonation, no fake production OTP, no P12 payment/accounting scope

---

## 3. Maximum One-Day Run Step Map

**Legend — Status column:** READY / PARTIAL / WARNING / BLOCKED / SKIP / BACKLOG  
**Legend — Strip:** Yes / No / N/A  
**Legend — Manager visibility:** Owner bridge / SM bridge / P8 dashboard / None

### 3.0 Pre-run — Operator / Run Controller (Steps 001–012)

| Step # | Flow area | Phase | Role | Screen / route | Action / decision | Required data | Expected before | Expected after | Status | Operator note |
|--------|-----------|-------|------|----------------|-------------------|---------------|-----------------|----------------|--------|---------------|
| 001 | Pre-run gate | Prep | OPERATOR | `erp-access-management.php` | Open access console; read readiness report | DB connection | UNKNOWN staff count | PASS/WARNING/BLOCKED known | **NEEDS OPERATOR CHECK** | BLOCKED if zero staff users |
| 002 | Pre-run gate | Prep | OPERATOR | Access console checks | Confirm ≥1 login-enabled staff per line role needed | `erp_company_users` rows | UNKNOWN | 6+ role assignments planned | **NEEDS OPERATOR CHECK** | Minimum: RECEPTION, SM, TECH, PARTS, FINANCE, QC |
| 003 | Pre-run gate | Prep | OPERATOR | `docs/access/MOGHARE360_V1_ONE_DAY_RUN_ACCESS_SETUP.md` | Verify checklist mentally against console | Doc | — | Briefing outline ready | **READY** | Prerequisite for P11.9-A |
| 004 | Demo data decision | Prep | OPERATOR | — | **Decide:** fresh `M360-DEMO` JobCard (recommended) vs JobCard ID 1 | — | — | Decision recorded | **READY** | **Use fresh M360-DEMO** per P11.9-0 |
| 005 | Demo data check | Prep | OPERATOR | `erp-soft-run-control-center.php` or demo finder logic | Check whether `M360-DEMO` JobCard exists in DB | Live DB | UNKNOWN | Exists or “will create in P11.9-A” | **NEEDS OPERATOR CHECK** | Do not create in P11.9-1 |
| 006 | OTP decision | Prep | OPERATOR | OTP diagnostics (host) | **Decide:** defer customer OTP legs vs configure SMS first | Config absent per P11.9-0 | SMS not configured | Deferral or configure plan | **WARNING** | Staff path OK without SMS |
| 007 | Runtime-hold briefing | Prep | OPERATOR | Staff Home / P11.9-0 §12 | Brief PARTS/FINANCE: part-use + payment-tracking disabled | — | — | Staff informed | **READY** | SKIP those pages in run |
| 008 | Navigation briefing | Prep | OPERATOR | P11.8-C report | Brief all: start Staff Home; Route Map **نمای عملیاتی** only | — | — | Staff informed | **READY** | 23 safe links; 40 protected |
| 009 | Dry-run log | Prep | OPERATOR | External run sheet (not in app) | Create step log with JobCard number, role handoffs, blockers | — | — | Log template ready | **READY** | Record OTP deferrals here |
| 010 | Owner alignment | Prep | OWNER | `erp-staff-home.php` or `erp-product-home.php` | Confirm oversight plan: bridge + P8 dashboards | Owner login | — | Owner briefed | **READY** | Owner may use Product Home landing |
| 011 | Route reference | Prep | OWNER | `erp-route-map.php?view=operational` | Verify operational view shows protected badges | — | Page loads | Owner accepts nav reference | **READY** | Not primary staff nav |
| 012 | Go/No-Go | Prep | OPERATOR | — | **Decision:** GO only if steps 001–002 not BLOCKED and 004–006 decided | Prior steps | — | GO or NO-GO recorded | **PARTIAL** | See §10 Go/No-Go table |

### 3.1 Flow 1 — Customer request or arrival (Steps 013–018)

| Step # | Flow area | Phase | Role | Screen / route | Action / decision | Required data | Expected before | Expected after | Status | Operator note |
|--------|-----------|-------|------|----------------|-------------------|---------------|-----------------|----------------|--------|---------------|
| 013 | Customer intake | P1 | CUSTOMER | `customer-request.php` | Submit online request **OR** skip if walk-in | Customer/vehicle fields | — | Request row **OR** walk-in path | **PARTIAL** | Online path optional |
| 014 | Path decision | P1 | OPERATOR | — | Record chosen path: **A** online request **B** walk-in JobCard | — | — | Path A or B locked | **READY** | Walk-in skips 015–017 |
| 015 | Reception login | P1 | RECEPTION | `staff-login.php` | Login with dedicated RECEPTION user | Staff credentials | User exists | Session active | **NEEDS OPERATOR CHECK** | BLOCKED if no RECEPTION user |
| 016 | Staff Home | P1 | RECEPTION | `erp-staff-home.php` | Confirm identity Persian labels; read «کار امروز» | Role RECEPTION | Logged in | Cards visible | **READY** | P11.7.1 polish applied |
| 017 | Online list | P1 | RECEPTION | `erp-reception-online-requests.php` | Open online requests board from Staff Home card | Path A | Request exists | List visible | **PARTIAL** | No operational shell |
| 018 | Online detail | P1 | RECEPTION | `erp-reception-online-request-detail.php?request_id=` | Open request from list; review inline status | `request_id` | Row in list | Detail visible | **PARTIAL** | Guided — not Route Map direct |

### 3.2 Flow 1 continued — Accept request (Steps 019–022)

| Step # | Flow area | Phase | Role | Screen / route | Action / decision | Required data | Expected before | Expected after | Status | Operator note |
|--------|-----------|-------|------|----------------|-------------------|---------------|-----------------|----------------|--------|---------------|
| 019 | Accept review | P1 | RECEPTION | Request detail | Verify customer/vehicle data complete | Request fields | Detail open | Ready to accept | **PARTIAL** | No responsibility strip |
| 020 | Accept action | P1 | RECEPTION | Detail form → POST accept | Submit accept from detail form only | CSRF token | Valid request | Accepted state | **READY** | Never open accept URL directly |
| 021 | Post-accept verify | P1 | RECEPTION | List or detail | Confirm status changed / flash message | — | POST success | Request accepted | **NEEDS OPERATOR CHECK** | DB state operator verifies |
| 022 | Handoff note | P1 | OPERATOR | Run log | Record request_id accepted → proceed to JobCard | `request_id` | Accepted | Log updated | **READY** | Skip 017–021 if walk-in |

### 3.3 Flow 2–3 — Reception JobCard create/progress (Steps 023–034)

| Step # | Flow area | Phase | Role | Screen / route | Action / decision | Required data | Expected before | Expected after | Status | Operator note |
|--------|-----------|-------|------|----------------|-------------------|---------------|-----------------|----------------|--------|---------------|
| 023 | JobCard board | P2 | RECEPTION | `erp-reception-jobcards.php` | Open JobCards board from Staff Home | Path A/B | Reception ready | Board visible | **READY** | Operational shell present |
| 024 | Locate/create JobCard | P2 | RECEPTION | JobCards board | Select existing `M360-DEMO` row **OR** create new JobCard | Demo JobCard prep | Board open | Target row selected | **NEEDS OPERATOR CHECK** | Creation = P11.9-A data prep |
| 025 | Open detail | P2 | RECEPTION | `erp-reception-jobcard-detail.php?jobcard_id=` | Open JobCard from board row | `jobcard_id` | Row exists | Detail open | **READY** | Guided card on Staff Home is info-only |
| 026 | Strip verify | P2 | RECEPTION | Detail responsibility strip | Read status, next action, customer name | JobCard row | Detail open | Strip populated or «ثبت نشده» | **READY** | P11.8-B-A strip |
| 027 | Reception action | P2 | RECEPTION | Detail form → POST jobcard action | Execute allowed reception action from form | CSRF, action name | Valid state | Updated JobCard status | **READY** | POST handler protected |
| 028 | Post-action verify | P2 | RECEPTION | Detail strip | Confirm status/next action updated | — | Action submitted | New state visible | **NEEDS OPERATOR CHECK** | |
| 029 | Shell nav check | P2 | RECEPTION | Shell top bar | Use **میز کار من** / **بازگشت** only | — | On detail | Nav works | **READY** | |
| 030 | Manager spot-check | P2 | P8 | OWNER: `erp-management-dashboard.php` | Owner verifies JobCard appears in pipeline view | P8 views | JobCard exists | Visible or UNKNOWN | **NEEDS OPERATOR CHECK** | Read-only oversight |
| 031 | Timeline guided | P2 | OWNER/SM | Staff Home info card / bridge | Open timeline **only** with `jobcard_id` from board context | `jobcard_id` | Known ID | Timeline or guided note | **PARTIAL** | Info card not direct nav |
| 032 | Record JobCard ID | P2 | OPERATOR | Run log | Write canonical `jobcard_id` + `M360-DEMO-*` number for all roles | JobCard identity | Known | All roles use same ID | **READY** | **Do not assume ID 1** |
| 033 | Empty board OK | P2 | OPERATOR | — | If board empty before JobCard created: accept if page clean | — | No PHP warnings | Proceed to P11.9-A create | **WARNING** | Empty ≠ broken |
| 034 | Handoff to contract | P2 | OPERATOR | Run log | Signal RECEPTION → contract gate step | JobCard ID | Reception complete | Next phase ready | **READY** | |

### 3.4 Flow 4 — Contract / signature gate (Steps 035–046)

| Step # | Flow area | Phase | Role | Screen / route | Action / decision | Required data | Expected before | Expected after | Status | Operator note |
|--------|-----------|-------|------|----------------|-------------------|---------------|-----------------|----------------|--------|---------------|
| 035 | Contract board | P1.5 | RECEPTION | `erp-intake-contracts.php` | Open intake contracts board | JobCard/customer link | Reception done | Board visible | **READY** | Shell present |
| 036 | Contract row | P1.5 | RECEPTION | Contracts board | Locate contract for demo JobCard | Contract row | Board open | Row selected | **NEEDS OPERATOR CHECK** | May need generate first |
| 037 | Contract detail | P1.5 | RECEPTION | `erp-intake-contract-detail.php?contract_id=` | Open contract detail from board | `contract_id` | Row exists | Detail open | **PARTIAL** | No shared strip |
| 038 | Generate decision | P1.5 | RECEPTION | Contract detail form | If no contract: POST generate from detail | CSRF | Missing contract | Contract generated | **READY** | Generate not on Staff Home card |
| 039 | Send decision | P1.5 | RECEPTION | Contract detail form | POST send to customer when ready | CSRF | Contract draft | Sent state | **READY** | |
| 040 | Gate verify | P1.5 | RECEPTION | Contract detail inline | Confirm contract gate status readable | Contract status | Actions done | Gate satisfied or gap noted | **NEEDS OPERATOR CHECK** | |
| 041 | Customer OTP decision | P1.5 | OPERATOR | — | **If OTP deferred:** record manual sign-off path | OTP plan | SMS absent | Deferral logged | **WARNING** | See §7 OTP matrix |
| 042 | Customer sign | P1.5 | CUSTOMER | `customer-intake-contract.php` / sign page | Customer reviews/signs **OR** step skipped per operator | Token/link | Sent contract | Signed or DEFERRED | **PARTIAL** | Needs token + optional SMS |
| 043 | Reception re-check | P1.5 | RECEPTION | Contract board/detail | Verify signed / gate cleared for JobCard | — | Customer step done | Gate OK for P3 | **NEEDS OPERATOR CHECK** | |
| 044 | Owner bridge | P1.5 | OWNER | Staff Home → bridge → contracts | Optional oversight open contracts board | — | — | Owner sees same board | **READY** | Manager Reference Bridge |
| 045 | Blocker record | P1.5 | OPERATOR | Run log | If gate fails: STOP downstream assign until fixed | Gate state | Failed gate | Blocker logged | **READY** | |
| 046 | Handoff to SM | P1.5 | OPERATOR | Run log | Signal SERVICE_MANAGER phase | Gate OK | Contract OK | SM notified | **READY** | |

### 3.5 Flow 5 — Service manager receive/assign (Steps 047–056)

| Step # | Flow area | Phase | Role | Screen / route | Action / decision | Required data | Expected before | Expected after | Status | Operator note |
|--------|-----------|-------|------|----------------|-------------------|---------------|-----------------|----------------|--------|---------------|
| 047 | SM login | P3 | SERVICE_MANAGER | `staff-login.php` | Login dedicated SM user | Credentials | User exists | Session active | **NEEDS OPERATOR CHECK** | |
| 048 | Staff Home | P3 | SERVICE_MANAGER | `erp-staff-home.php` | Review «کار امروز» + coordination bridge | SM role | Logged in | Cards visible | **READY** | |
| 049 | Technical board | P3 | SERVICE_MANAGER | `erp-technical-board.php` | Open board from today card | JobCard in queue | Gate passed | Board visible | **READY** | Shell present |
| 050 | Select JobCard | P3 | SERVICE_MANAGER | Technical board | Find demo JobCard row | `jobcard_id` | Row on board | Row selected | **NEEDS OPERATOR CHECK** | May be empty until prior steps |
| 051 | Technical detail | P3 | SERVICE_MANAGER | `erp-technical-jobcard-detail.php?jobcard_id=` | Open detail from board | `jobcard_id` | Row selected | Detail open | **READY** | |
| 052 | Strip verify | P3 | SERVICE_MANAGER | Responsibility strip | Confirm assignee/responsible/next action | JobCard actors | Detail open | Strip read | **READY** | |
| 053 | Assign technician | P3 | SERVICE_MANAGER | Detail form → POST technical action | Execute `assign_technician` (or allowed action) | CSRF, technician | Unassigned | Technician assigned | **READY** | From detail only |
| 054 | Post-assign verify | P3 | SERVICE_MANAGER | Strip / flash | Confirm assignee updated | — | POST OK | Assigned state | **NEEDS OPERATOR CHECK** | |
| 055 | SM coordination bridge | P3 | SERVICE_MANAGER | Staff Home bridge refs | Optional cross-check parts/payment refs — **skip disabled cards** | — | — | SM aware of holds | **WARNING** | part-use/payment = runtime_hold |
| 056 | Handoff to TECH | P3 | OPERATOR | Run log | Notify TECHNICIAN same `jobcard_id` | JobCard ID | Assigned | TECH phase ready | **READY** | |

### 3.6 Flow 6 — Technician diagnosis (Steps 057–066)

| Step # | Flow area | Phase | Role | Screen / route | Action / decision | Required data | Expected before | Expected after | Status | Operator note |
|--------|-----------|-------|------|----------------|-------------------|---------------|-----------------|----------------|--------|---------------|
| 057 | TECH login | P3 | TECHNICIAN | `staff-login.php` | Login dedicated TECH user | Credentials | User exists | Session active | **NEEDS OPERATOR CHECK** | |
| 058 | Staff Home | P3 | TECHNICIAN | `erp-staff-home.php` | Open «تابلو فنی» card | TECH role | Logged in | Card visible | **READY** | No “my jobs” filter — backlog |
| 059 | Technical board | P3 | TECHNICIAN | `erp-technical-board.php` | Open board | Assignment done | Board loads | Row visible | **READY** | |
| 060 | Open detail | P3 | TECHNICIAN | `erp-technical-jobcard-detail.php?jobcard_id=` | Open assigned JobCard | `jobcard_id` | On board | Detail open | **READY** | |
| 061 | Strip verify | P3 | TECHNICIAN | Strip | Confirm assigned to self or unit | Assignee | Detail open | Strip OK | **READY** | |
| 062 | Diagnosis action | P3 | TECHNICIAN | Detail form → POST | Submit diagnosis / technical progression action | CSRF | Valid state | Technical state advanced | **READY** | |
| 063 | Verify diagnosis | P3 | TECHNICIAN | Strip | Read updated status + next action | — | POST OK | State updated | **NEEDS OPERATOR CHECK** | |
| 064 | Part-use skip | P3 | TECHNICIAN | Staff Home part-use card | **Do not click** — card shows runtime_hold | — | — | Skip recorded | **SKIP** | Use PARTS reserve instead |
| 065 | Owner timeline | P3 | OWNER | `erp-jobcard-timeline.php?jobcard_id=` | Optional audit from bridge with known ID | `jobcard_id` | Known | Timeline viewed | **PARTIAL** | Guided context required |
| 066 | Handoff PARTS/FINANCE | P3 | OPERATOR | Run log | Signal parts + estimate parallel readiness | JobCard ID | Diagnosis done | Next phases ready | **READY** | |

### 3.7 Flow 7 — Parts reserve/use (Steps 067–074)

| Step # | Flow area | Phase | Role | Screen / route | Action / decision | Required data | Expected before | Expected after | Status | Operator note |
|--------|-----------|-------|------|----------------|-------------------|---------------|-----------------|----------------|--------|---------------|
| 067 | PARTS login | P4/P5 | PARTS | `staff-login.php` | Login PARTS user | Credentials | User exists | Session active | **NEEDS OPERATOR CHECK** | |
| 068 | Staff Home | P4/P5 | PARTS | `erp-staff-home.php` | Review reserve card; note part-use disabled | PARTS role | Logged in | Cards visible | **READY** | |
| 069 | Part reserve | P4/P5 | PARTS | `erp-part-reserve.php` | Open reserve page from Staff Home | `jobcard_id` | Diagnosis done | Page loads | **READY** | **USE** path |
| 070 | Reserve action | P4/P5 | PARTS | Part reserve UI | Reserve parts for demo JobCard | Part SKUs/qty | Page open | Reserve recorded | **NEEDS OPERATOR CHECK** | Operator verifies DB/UI |
| 071 | Part-use skip | P4/P5 | PARTS | `erp-jobcard-part-use.php` | **SKIP** — runtime_hold; record manual observation if needed | — | — | BACKLOG noted | **SKIP** | Fix before re-enable — backlog |
| 072 | Purchase list skip | P4/P5 | PARTS | Backlog card purchase list | **BACKLOG** — page missing | — | — | Not in dry run | **BACKLOG** | |
| 073 | SM bridge check | P4/P5 | SERVICE_MANAGER | Coordination bridge part reserve ref | Optional verify parts step done | — | Reserve done | SM informed | **READY** | part-use ref disabled on bridge |
| 074 | Handoff FINANCE | P4/P5 | OPERATOR | Run log | Signal estimate phase | JobCard ID | Parts reserved | FINANCE ready | **READY** | |

### 3.8 Flow 8 — Estimate / payment / invoice coordination (Steps 075–086)

| Step # | Flow area | Phase | Role | Screen / route | Action / decision | Required data | Expected before | Expected after | Status | Operator note |
|--------|-----------|-------|------|----------------|-------------------|---------------|-----------------|----------------|--------|---------------|
| 075 | FINANCE login | P4 | FINANCE | `staff-login.php` | Login FINANCE user | Credentials | User exists | Session active | **NEEDS OPERATOR CHECK** | |
| 076 | Staff Home | P4 | FINANCE | `erp-staff-home.php` | Note payment-tracking card disabled | FINANCE role | Logged in | Cards visible | **READY** | |
| 077 | Payment tracking skip | P4 | FINANCE | `erp-payment-tracking.php` | **SKIP** — runtime_hold | — | — | Use boards only | **SKIP** | Verify payment via settlement |
| 078 | Estimate board | P4 | FINANCE | `erp-estimate-board.php` | Open estimate board | JobCard mid-flow | Logged in | Board visible | **READY** | Shell present |
| 079 | Estimate detail | P4 | FINANCE | `erp-estimate-detail.php?jobcard_id=` | Open from board | `jobcard_id` | Row exists | Detail open | **READY** | Strip present |
| 080 | Estimate action | P4 | FINANCE | Detail form → POST estimate action | Progress estimate workflow | CSRF | Valid state | Estimate advanced | **READY** | |
| 081 | Customer approval decision | P4 | OPERATOR | — | **If OTP deferred:** record manual customer approval path | OTP plan | — | Deferral logged | **WARNING** | |
| 082 | Customer estimate | P4 | CUSTOMER | `customer-estimate-approval.php` / sign | Approve estimate **OR** defer | Token | Estimate ready | Approved or DEFERRED | **PARTIAL** | |
| 083 | FINANCE verify | P4 | FINANCE | Estimate detail strip | Confirm approval state | — | Customer step | Gate OK | **NEEDS OPERATOR CHECK** | |
| 084 | Owner financial view | P4 | OWNER | `erp-financial-control-summary.php` | Optional read-only oversight | P8 view | Estimate exists | Summary visible | **NEEDS OPERATOR CHECK** | |
| 085 | Invoice board prep | P7 | FINANCE | `erp-final-invoice-board.php` | Note board for later settlement phase | — | Estimate OK | Board located | **READY** | Used again step 095 |
| 086 | Handoff work exec | P4/P5 | OPERATOR | Run log | Signal work execution after estimate gates | JobCard ID | Estimate OK | P5 ready | **READY** | |

### 3.9 Flow 9 — Work execution (Steps 087–094)

| Step # | Flow area | Phase | Role | Screen / route | Action / decision | Required data | Expected before | Expected after | Status | Operator note |
|--------|-----------|-------|------|----------------|-------------------|---------------|-----------------|----------------|--------|---------------|
| 087 | Work board | P5 | TECHNICIAN | `erp-work-execution-board.php` | Open from Staff Home | Estimate gates | TECH logged in | Board visible | **READY** | SM may also monitor |
| 088 | Work detail | P5 | TECHNICIAN | `erp-work-execution-detail.php?jobcard_id=` | Open JobCard from board | `jobcard_id` | On board | Detail open | **READY** | Strip present |
| 089 | Strip verify | P5 | TECHNICIAN | Strip | Confirm work status + next action | — | Detail open | Strip read | **READY** | |
| 090 | Work action | P5 | TECHNICIAN | Detail form → POST work action | Execute allowed work action | CSRF | Valid state | Work progressed | **READY** | |
| 091 | Verify completion | P5 | TECHNICIAN | Strip | Confirm ready for QC or next state | — | POST OK | State updated | **NEEDS OPERATOR CHECK** | |
| 092 | SM board check | P5 | SERVICE_MANAGER | `erp-work-execution-board.php` | Optional coordination oversight | — | Work done | SM confirms | **READY** | |
| 093 | Bottleneck view | P5 | OWNER/SM | `erp-bottleneck-monitor.php` | Optional queue oversight | P8 | — | Dashboard read | **READY** | Read-only |
| 094 | Handoff QC | P5 | OPERATOR | Run log | Signal QC phase | JobCard ID | Work complete | QC ready | **READY** | |

### 3.10 Flow 10 — QC (Steps 095–102)

| Step # | Flow area | Phase | Role | Screen / route | Action / decision | Required data | Expected before | Expected after | Status | Operator note |
|--------|-----------|-------|------|----------------|-------------------|---------------|-----------------|----------------|--------|---------------|
| 095 | QC login | P6 | QC | `staff-login.php` | Login QC user | Credentials | User exists | Session active | **NEEDS OPERATOR CHECK** | |
| 096 | QC board | P6 | QC | `erp-qc-board.php` | Open from Staff Home | Work complete | Logged in | Board visible | **READY** | Shell present |
| 097 | QC detail | P6 | QC | `erp-qc-detail.php?jobcard_id=` | Open from board | `jobcard_id` | Row exists | Detail open | **READY** | Strip + delivery fields |
| 098 | QC action | P6 | QC | Detail form → POST QC action | Execute QC / delivery readiness action | CSRF | Valid state | QC progressed | **READY** | |
| 099 | Delivery readiness | P6 | QC | Strip / detail | Verify `delivery_readiness_status` readable | QC data | Action done | READY or gap | **NEEDS OPERATOR CHECK** | |
| 100 | Delivery control | P6 | QC | `erp-delivery-control.php` | Open from Staff Home (not in route registry) | JobCard ready | QC done | Control page open | **PARTIAL** | USE WITH GUIDE |
| 101 | Owner QC view | P6 | OWNER | Bridge → QC board | Optional oversight | — | — | Owner confirms | **READY** | |
| 102 | Handoff settlement | P6 | OPERATOR | Run log | Signal FINANCE settlement | JobCard ID | QC OK | P7 ready | **READY** | |

### 3.11 Flow 11–12 — Settlement / delivery / close (Steps 103–112)

| Step # | Flow area | Phase | Role | Screen / route | Action / decision | Required data | Expected before | Expected after | Status | Operator note |
|--------|-----------|-------|------|----------------|-------------------|---------------|-----------------|----------------|--------|---------------|
| 103 | Invoice board | P7 | FINANCE | `erp-final-invoice-board.php` | Re-open board for closing phase | QC ready | FINANCE session | Board visible | **READY** | |
| 104 | Invoice detail | P7 | FINANCE | `erp-final-invoice-detail.php?jobcard_id=` | Open from board | `jobcard_id` | Row exists | Detail open | **READY** | Strip present |
| 105 | Invoice action | P7 | FINANCE | Detail form → POST | Progress final invoice action | CSRF | Valid state | Invoice advanced | **READY** | |
| 106 | Settlement detail | P7 | FINANCE | `erp-settlement-detail.php?jobcard_id=` | Open from invoice flow / guided path | `jobcard_id` | Invoice state | Detail open | **READY** | Strip present |
| 107 | Settlement action | P7 | FINANCE | Detail form → POST settlement action | Execute settlement step | CSRF | Valid state | Settlement progressed | **READY** | |
| 108 | Payment verify | P7 | FINANCE | Settlement strip | Verify payment/settlement state **without** payment-tracking page | Settlement data | Actions done | Paid/settled or gap | **NEEDS OPERATOR CHECK** | payment-tracking SKIP |
| 109 | Customer delivery decision | P7 | OPERATOR | — | **If OTP deferred:** record manual delivery confirmation | OTP plan | — | Deferral logged | **WARNING** | |
| 110 | Customer delivery | P7 | CUSTOMER | `customer-delivery-review.php` / sign | Delivery sign **OR** defer | Token | Settlement OK | Closed or DEFERRED | **PARTIAL** | |
| 111 | JobCard close verify | P7 | FINANCE/QC | Detail strip / delivery control | Confirm close/delivery readiness | — | Customer step | JobCard closed or near | **NEEDS OPERATOR CHECK** | |
| 112 | End-of-run owner review | P7/P8 | OWNER | `erp-owner-control-center.php` + timeline | Final oversight read-only review | Full run log | Flow complete | Owner sign-off on dry run | **READY** | No impersonation |

### 3.12 Post-run — Operator (Steps 113–115)

| Step # | Flow area | Phase | Role | Screen / route | Action / decision | Required data | Expected before | Expected after | Status | Operator note |
|--------|-----------|-------|------|----------------|-------------------|---------------|-----------------|----------------|--------|---------------|
| 113 | Soft Run optional | P9 | OPERATOR | `erp-end-to-end-demo-scenario.php` | Record NOT_RUN → optional manual status update | Demo JobCard | Run complete | Scenario noted | **BACKLOG** | Automation optional |
| 114 | Blocker rollup | Post | OPERATOR | Run log | Summarize BLOCKED/WARNING/SKIP items | Log | — | Report for P11.9-A+ | **READY** | |
| 115 | Next phase decision | Post | OPERATOR | — | Choose **Dry Run Pack complete** vs **Fix Pack** backlog | Rollup | — | Phase plan set | **READY** | See §12 |

**Total maximum operational steps defined: 115** (includes pre/post-run operator controls and explicit SKIP/DEFER decisions).

---

## 4. Role-by-Role Execution Checklist

| Role | Login required? | Staff Home path | First page | Main responsibility | Pages used | Must report back to | Gap | Status |
|------|-----------------|-----------------|------------|---------------------|------------|---------------------|-----|--------|
| **OWNER / SYSTEM_ADMIN** | Yes (owner or staff+company) | `erp-staff-home.php` → Manager Reference Bridge | `erp-access-management.php` or Product Home | Oversight, access, diagnostics | Bridge boards, P8 dashboards, route map ops view, timeline (guided) | OPERATOR / owner self | Product Home default not Staff Home | **READY** |
| **RECEPTION** | Yes (staff) | Staff Home → کار امروز | `erp-reception-online-requests.php` **or** jobcards board | Intake, JobCard, contract gate | P1 list/detail, P2 board/detail, P1.5 board/detail | OPERATOR + SM | P1/P1.5 no shell | **PARTIAL** |
| **SERVICE_MANAGER** | Yes | Staff Home → کار امروز + coordination bridge | `erp-technical-board.php` | Assign, coordinate units | P3/P5/P6 boards, technical detail | OPERATOR + OWNER | Assignment from detail not card | **READY** |
| **TECHNICIAN** | Yes | Staff Home → کار امروز | `erp-technical-board.php` | Diagnosis + work execution | P3/P5 boards + details | SM + OPERATOR | part-use disabled; no my-jobs filter | **PARTIAL** |
| **PARTS** | Yes | Staff Home → کار امروز | `erp-part-reserve.php` | Reserve parts | Reserve page only (part-use SKIP) | SM + OPERATOR | part-use hold; purchase list backlog | **PARTIAL** |
| **FINANCE** | Yes | Staff Home → کار امروز | `erp-estimate-board.php` | Estimate, invoice, settlement | P4/P7 boards + details; payment-tracking SKIP | OPERATOR + OWNER | payment-tracking hold | **PARTIAL** |
| **QC** | Yes | Staff Home → کار امروز | `erp-qc-board.php` | QC + delivery control | P6 board/detail, delivery control | OPERATOR + FINANCE | delivery-control not in registry | **PARTIAL** |
| **CUSTOMER** | No staff login | Public customer pages | `customer-request.php` or token links | Request, sign, approve, delivery | customer-* pages | RECEPTION / OPERATOR | OTP/SMS dependent | **PARTIAL** |
| **OPERATOR / RUN CONTROLLER** | Owner or admin | Access console + external log | `erp-access-management.php` | Go/No-Go, briefing, logging, deferrals | All oversight pages read-only | OWNER | Live DB state UNKNOWN until check | **PARTIAL** |

---

## 5. Required Demo Data Checklist

| Data object | Required for step(s) | Exists now? | Can use existing JobCard 1? | Needs fresh M360-DEMO? | Risk | Recommendation |
|-------------|---------------------|-------------|----------------------------|------------------------|------|----------------|
| Staff users (6 roles) | 015, 047, 057, 067, 075, 095 | **NEEDS OPERATOR CHECK** | N/A | N/A | BLOCKED if missing | Create in **P11.9-A** via access mgmt |
| Customer record | 024, 035+ | **NEEDS OPERATOR CHECK** | Unknown | **Yes** with demo JobCard | Wrong customer linkage | Create with demo JobCard |
| Vehicle record | 024 | **NEEDS OPERATOR CHECK** | Unknown | **Yes** | Missing plate/VIN | Create with demo JobCard |
| Online request (optional) | 013–021 | **NEEDS OPERATOR CHECK** | Maybe | Optional | Walk-in avoids | Path B skips |
| JobCard `M360-DEMO-*` | 024–111 | **NEEDS OPERATOR CHECK** | **Not recommended** | **Yes — safer** | ID 1 may be stale/wrong | **Fresh M360-DEMO in P11.9-A** |
| JobCard ID 1 | — | **NEEDS OPERATOR CHECK** | **No — avoid** | Prefer fresh | Placeholder links in some pages | Do not standardize on ID 1 |
| Intake contract | 035–043 | **NEEDS OPERATOR CHECK** | Unknown | With demo JobCard | Blocks P3 | Generate from contract detail |
| Estimate row | 078–083 | **NEEDS OPERATOR CHECK** | Unknown | With demo JobCard | Blocks work/QC | FINANCE creates via UI |
| Part reservation | 069–070 | **NEEDS OPERATOR CHECK** | Unknown | With demo JobCard | part-use skipped | Reserve only |
| QC check | 097–099 | **NEEDS OPERATOR CHECK** | Unknown | With demo JobCard | Blocks delivery | QC actions from detail |
| Final invoice / settlement | 103–108 | **NEEDS OPERATOR CHECK** | Unknown | With demo JobCard | Blocks close | FINANCE board flow |
| P8 SQL views | 030, 084, 093 | **NEEDS OPERATOR CHECK** | N/A | N/A | Dashboards empty/WARN | Verify migration applied |

---

## 6. Page Runtime Decision Matrix

| Page / route | Used in dry run? | Current readiness | Clickable from safe nav? | Runtime risk | Decision |
|--------------|------------------|-------------------|--------------------------|--------------|----------|
| `erp-staff-home.php` | Yes | READY | Yes — all roles | Low | **USE** |
| `erp-route-map.php` (operational) | Yes — reference | READY | Yes — 23 ops links | Low if ops view | **USE** |
| `erp-reception-online-requests.php` | Yes (path A) | PARTIAL | Yes — Staff Home | Legacy layout | **USE WITH GUIDE** |
| `erp-reception-online-request-detail.php` | Yes | PARTIAL | Guided only | No strip | **USE WITH GUIDE** |
| `erp-reception-jobcards.php` | Yes | READY | Yes | Low | **USE** |
| `erp-reception-jobcard-detail.php` | Yes | READY | Guided from board | Low | **USE** |
| `erp-intake-contracts.php` | Yes | READY | Yes | Low | **USE** |
| `erp-intake-contract-detail.php` | Yes | PARTIAL | Guided from board | No strip | **USE WITH GUIDE** |
| `erp-technical-board.php` | Yes | READY | Yes | Empty OK | **USE** |
| `erp-technical-jobcard-detail.php` | Yes | READY | Guided | Low | **USE** |
| `erp-part-reserve.php` | Yes | READY | Yes — PARTS | Low | **USE** |
| `erp-jobcard-part-use.php` | No | runtime_hold | **No** | Not product-ready | **SKIP** — backlog fix |
| `erp-estimate-board.php` | Yes | READY | Yes | Low | **USE** |
| `erp-estimate-detail.php` | Yes | READY | Guided | Low | **USE** |
| `erp-payment-tracking.php` | No | runtime_hold | **No** | Load issues reported | **SKIP** — use FI/settlement |
| `erp-work-execution-board.php` | Yes | READY | Yes | Low | **USE** |
| `erp-work-execution-detail.php` | Yes | READY | Guided | Low | **USE** |
| `erp-qc-board.php` | Yes | READY | Yes | Low | **USE** |
| `erp-qc-detail.php` | Yes | READY | Guided | Low | **USE** |
| `erp-delivery-control.php` | Yes | PARTIAL | QC Staff Home only | Not in registry | **USE WITH GUIDE** |
| `erp-final-invoice-board.php` | Yes | READY | Yes | Low | **USE** |
| `erp-final-invoice-detail.php` | Yes | READY | Guided | Low | **USE** |
| `erp-settlement-detail.php` | Yes | READY | Guided | Low | **USE** |
| `customer-*` pages | Conditional | PARTIAL | Not ops Route Map links | OTP/token | **USE WITH GUIDE** or defer |
| `*-action.php` endpoints | Indirect only | READY (protected) | **No** | GET redirects | **USE** via forms only |
| `erp-management-dashboard.php` | Oversight | READY | Owner bridge | View-dependent | **USE** |
| `erp-jobcard-timeline.php` | Oversight | PARTIAL | Guided | Needs jobcard_id | **USE WITH GUIDE** |

---

## 7. OTP / Customer Interaction Decision Matrix

| Customer step | Needs OTP? | SMS configured? | Can be simulated? | Production-safe? | Dry-run decision | Risk |
|---------------|------------|-----------------|-------------------|------------------|------------------|------|
| Online request submit | No | N/A | Yes — form | Yes | **USE** | Low |
| Contract send/sign | Yes — if live sign | **No** (P11.9-0) | Manual witness/defer only | Fake OTP forbidden | **DEFER or manual log** | Medium |
| Estimate customer approval | Yes — if live approve | **No** | Manual/defer | Fake OTP forbidden | **DEFER or manual log** | Medium |
| Delivery customer sign | Yes — if live delivery | **No** | Manual/defer | Fake OTP forbidden | **DEFER or manual log** | Medium |
| Staff workflow P2–P7 | No | N/A | N/A | Yes | **PROCEED without SMS** | Low |

**Operator recording rule:** Any deferred OTP leg must be logged in run sheet with: step #, reason «SMS not configured — deferred per P11.9-1», and substitute verification (e.g. owner witnessed verbal OK).

---

## 8. Manager / Owner Control Matrix

| Flow stage | What manager must see | Existing page/dashboard | Visibility now | Can intervene? | Must not do | Gap | Status |
|------------|----------------------|-------------------------|----------------|----------------|-------------|-----|--------|
| Pre-run access | Staff users exist | `erp-access-management.php` | READY if DB OK | Create users (ops) | Change Auth architecture | Staff count UNKNOWN | **NEEDS OPERATOR CHECK** |
| P1 intake | Requests progressing | Bridge → online requests | READY | Read-only oversight | Accept on behalf without RECEPTION login | P1 no shell | **PARTIAL** |
| P2 JobCard | JobCard created | Management dashboard / timeline | READY/PARTIAL | Read-only | Impersonate reception | Timeline needs ID | **PARTIAL** |
| P1.5 contract | Gate status | Bridge → contracts | READY | Read-only | Forge customer sign | OTP may defer | **PARTIAL** |
| P3 assign | Technician assigned | Technical board + strip | READY | SM acts; owner watches | Override engine | — | **READY** |
| P4 estimate | Estimate/approval | Financial summary + estimate board | READY/PARTIAL | FINANCE acts | Fake OTP | Customer leg may defer | **PARTIAL** |
| P5 work | Work progressing | Work board + bottleneck | READY | TECH acts | — | — | **READY** |
| P6 QC | QC + delivery readiness | QC board + delivery control | READY/PARTIAL | QC acts | — | delivery-control registry gap | **PARTIAL** |
| P7 close | Invoice/settlement/close | FI board + owner control | READY | FINANCE acts | Payment gateway | payment-tracking skip | **PARTIAL** |
| Navigation audit | Safe routes | Route map ops view | READY | Reference only | Use technical view for staff ops | — | **READY** |

---

## 9. Blocking vs Warning Register

| Issue | Severity | Affects step(s) | Dry-run impact | Must fix before dry run? | Recommended action | Recommended phase |
|-------|----------|-----------------|----------------|--------------------------|-------------------|-------------------|
| Zero dedicated staff users | BLOCKED | 015, 047, 057, 067, 075, 095 | Cannot run role map | **Yes** | Provision 6 roles in access mgmt | **P11.9-A Dry Run Pack** |
| No `M360-DEMO` JobCard | HIGH | 024–111 | No end-to-end trace | **Yes** for meaningful run | Create fresh demo JobCard + customer/vehicle | **P11.9-A** |
| Using JobCard ID 1 | MEDIUM | All JobCard steps | Wrong/stale data | **Avoid** | Use fresh M360-DEMO | **P11.9-A** |
| SMS/OTP not configured | MEDIUM | 041–042, 081–082, 109–110 | Customer legs incomplete | **Only if** live OTP required | Defer customer OTP + log | Operator decision / later OTP config |
| part-use runtime hold | LOW | 064, 071 | Consumption UI skipped | **No** | Reserve + manual observation | Fix Pack backlog |
| payment-tracking hold | LOW | 077, 108 | Payment page skipped | **No** | FI/settlement verification | Fix Pack backlog |
| P1/P1.5 no shell | LOW | 017–018, 037 | UX friction | **No** | Operator guide | P11.9-B or backlog |
| Action raw GET on legacy pages | LOW | — if mis-navigated | Confusion | **No** | Follow nav rules | P11.9-C backlog |
| E2E Demo NOT_RUN | INFO | 113 | None for manual run | **No** | Optional post-run | P9 backlog |
| Link Audit doc warnings | INFO | — | None | **No** | P10 packaging | Backlog |

---

## 10. Go / No-Go Decision Table

| Area | Status | Reason | Go condition | No-go condition | Decision |
|------|--------|--------|--------------|-----------------|----------|
| UI navigation (Staff Home, shell P2–P7, Route Map ops) | **READY** | P11.7–P11.8 complete | Brief staff on board→detail | — | **GO** |
| Manager oversight | **READY** | Bridge + P8 dashboards | Owner assigned oversight | — | **GO** |
| Staff user provisioning | **UNKNOWN** | Live DB not verified in this report | ≥6 role logins enabled | Access console BLOCKED | **CONDITIONAL** |
| Demo JobCard data | **UNKNOWN** | No repo seed | Fresh M360-DEMO exists or planned in P11.9-A | No JobCard plan | **CONDITIONAL** |
| OTP customer legs | **WARNING** | SMS absent | Deferral documented | Owner insists live OTP without config | **CONDITIONAL GO** |
| part-use / payment pages | **WARNING** | Runtime hold | Accept SKIP + workarounds | Team refuses workaround | **GO with SKIP** |
| Action endpoints | **READY** | Not linked; form-only | Train staff | — | **GO** |
| Overall dry run | **CONDITIONAL GO** | P11.9-0 PARTIAL | P11.9-A prep complete | Staff or JobCard missing | **NO-GO until P11.9-A** |

---

## 11. Final Persian Answers

### 1. حداکثر چند قدم عملیاتی برای Dry Run لازم است؟

**۱۱۵ قدم** در این نقشه (شامل آماده‌سازی اپراتور، ۱۲ گام اصلی کارگاه، تصمیم‌های SKIP/DEFER، و جمع‌بندی پایان). برای اجرای واقعی با شاخه walk-in یا online ممکن است **~۹۰–۱۰۵ قدم** اجرا شود.

### 2. حداقل چند نقش باید قبل از Dry Run ساخته شود؟

**۶ نقش پرسنلی خط:** RECEPTION، SERVICE_MANAGER، TECHNICIAN، PARTS، FINANCE، QC — به‌علاوه **OWNER** (یا SYSTEM_ADMIN) برای راهبری. ساخت کاربران پیش‌نیاز **P11.9-A** است.

### 3. آیا بهتر است JobCard 1 استفاده شود یا JobCard تازه با کد M360-DEMO؟

**JobCard تازه با پیشوند `M360-DEMO-*` امن‌تر است** برای ردیابی و جلوگیری از داده کهنه. **JobCard ID 1 توصیه نمی‌شود.**

### 4. آیا بدون OTP واقعی می‌توان Dry Run را انجام داد؟

**بله — برای مسیر پرسنلی P2–P7.** مراحل OTP مشتری (قرارداد، برآورد، تحویل) باید **موکول** یا **ثبت دستی** شوند و در log اپراتور ثبت گردد.

### 5. آیا part-use و payment-tracking باید قبل از Dry Run اصلاح شوند؟

**خیر — blocker نیستند.** تصمیم:this report: **SKIP UI**؛ part-use → `erp-part-reserve.php`؛ payment → board فاکتور/تسویه. اصلاح در **Fix Pack** backlog.

### 6. آیا action endpointهای خام مانع اجرا هستند؟

**خیر** — از ناوبری حذف شده‌اند؛ GET به board redirect می‌شود. فقط در باز کردن مستقیم URL legacy **WARNING** است.

### 7. آیا Route Map عملیاتی برای راهبری کافی است؟

**بله — به‌عنوان مرجع راهبری** (نمای عملیاتی). میز کار پرسنل مسیر اصلی است؛ Route Map مکمل OWNER/اپراتور.

### 8. آیا مدیر/مالک می‌تواند از ابتدا تا انتها کنترل کند؟

**بله — read-only + bridge.** Manager Reference Bridge، داشبورد P8، Owner Control، KPI، گلوگاه، timeline (با jobcard_id)، و Route Map ops. **بدون impersonation و بدون override engine.**

### 9. Go / No-Go پیشنهادی چیست؟

**Conditional GO:** پس از **P11.9-A Dry Run Pack** (کاربران + JobCard M360-DEMO + briefing). **No-Go** اگر staff user یا JobCard نمونه آماده نباشد.

### 10. کمترین فاز بعدی چیست: Dry Run Pack یا Fix Pack?

**اول Dry Run Pack (P11.9-A)** — provisioning + demo data + operator script. **Fix Pack** بعداً برای part-use، payment-tracking، shell P1/P1.5، OTP config.

---

## 12. Recommended Next Phase

| Phase | Name | Scope | Why first |
|-------|------|-------|-----------|
| **P11.9-A** | **Dry Run Pack** | Staff user creation checklist, fresh `M360-DEMO` JobCard procedure, operator run sheet, briefing deck | Unblocks CONDITIONAL GO items without code change |
| P11.9-B | Operational shell P1/P1.5 | UI-only | UX — not blocker |
| P11.9-C | Action security message UI | UI-only | Warning cleanup |
| Fix Pack | Runtime holds | Browser validate + re-enable part-use/payment | After dry run finds real gaps |
| P12 | Finance center, purchase list, payment gateway | Excluded V1 | Post dry run |

### Special decisions (§G) — explicit answers

| # | Topic | Decision |
|---|-------|----------|
| 1 | Staff users | **6 line roles + OWNER** must exist; creating them is **prerequisite P11.9-A**, not P11.9-1 |
| 2 | Demo JobCard | **Fresh `M360-DEMO-*`** — not JobCard ID 1 |
| 3 | OTP/SMS | **Not required** for staff dry run; **defer** customer legs; log deferrals |
| 4 | Part use | **SKIP page** — use reserve; **manual observation backlog**; fix later |
| 5 | Payment tracking | **SKIP page** — verify via **final invoice/settlement only**; fix later |
| 6 | Action endpoints | **Not blockers** — form-only; train staff |
| 7 | Shell gaps (P1, parts pages, delivery) | **WARNING / USE WITH GUIDE** — not blockers |
| 8 | Route Map | **Safe enough** — **نمای عملیاتی** for reference |

---

## Security Confirmation (Report Scope)

- No DB change
- No SQL migration
- No Auth/Login change
- No permission/role seed change
- No workflow state change
- No action handler change
- No OTP secret exposure
- No production fake OTP
- No impersonation
- No manager override
- No HR self-service
- No P12 scope

---

P11.9-1 maps the maximum operational steps, roles, screens, data requirements, decision points, blockers and operator controls needed for a controlled One-Day Run dry run before any staff provisioning, demo data creation, workflow execution, code change, SQL change, permission change, Auth/Login change, or P12 scope.
