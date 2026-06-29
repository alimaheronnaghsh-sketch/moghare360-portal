# MOGHARE360 P11.8-B-0 — Operational Navigation / Document Responsibility / Workflow Status Discovery Report

**Phase:** P11.8-B-0  
**Mode:** REPORT ONLY — no code, SQL, Auth, permissions, roles, route maps, or new pages modified  
**Date:** 2026-06-26  
**Scope:** P1–P7 operational pages, Staff/Management hubs, helpers, migrations, audit docs

---

## 1. Executive Summary

MOGHARE360 **already has strong backend foundations** for workflow status, actor user IDs, event/history tables, and allowed-action engines across P1–P7. It **does not have a unified operational page shell** for navigation (back / workbench / breadcrumb) or for document responsibility display (requester, approver, performer, current owner).

Navigation is **fragmented into four independent patterns**:

1. **Staff Home** (`erp-staff-home.php`) — role workbench entry, forward-only cards  
2. **Product Home + RC nav** (`erp-product-home.php`, `m360_nav_rc_links()`) — owner/module catalog  
3. **P8 management nav** (`m360_mgmt_nav_links()`) — dashboards only  
4. **Ad-hoc one-step back links** on detail pages (`← بازگشت به برد …`)

**Zero P1–P7 operational pages link back to Staff Home or Product Home.** Breadcrumb exists only in the **UI shell demo** (`moghare360-ui-shell.php`), not on live operational pages.

Responsibility data **exists in DB** (`assigned_technician_user_id`, `created_by_user_id`, `closed_by_user_id`, etc.) but **operational UI rarely resolves or displays it**. Status is shown per-domain; next action is inferred from **allowed-action button lists**, not a single “اقدام بعدی” label. P8 management (`m360_mgmt_resolve_stage()`) has the richest pipeline model but **operational pages do not reuse it**.

**Recommendation:** Next safe step is **UI-only operational shell** (navigation bar + responsibility strip + status/next-action panel) wired to **existing helpers and columns** — no new DB tables, no permission model change, no workflow state machine change.

---

## 2. Owner Requirement Interpretation

The owner observes two gaps:

1. **Navigation:** Users enter boards from Staff Home / Product Home but cannot easily return; no breadcrumb or operational route context on P1–P7 pages.  
2. **Document clarity:** Operational documents do not clearly show who requested, approved, must perform, currently owns the file, or what action is required (return / approve / perform / close).

P11.8-B-0 must determine whether these are **greenfield builds** or **connect/upgrade** of existing patterns. Verdict: **connect/upgrade** — backend and partial UI patterns exist; missing layer is a **shared operational document shell** and **user-ID → display-name resolution**.

---

## 3. Existing Navigation Capability Discovery

### 3.1 Standing rule applied

| Question | Answer |
|----------|--------|
| Similar capability exists? | **Partial** — multiple nav patterns, no unified shell |
| Where? | See table below |
| Complete or fragmented? | **Fragmented / disconnected** |
| Upgrade vs build? | **Upgrade/connect** existing hubs + registry + back-link CSS |
| Minimal build if none? | Shared `m360-operational-nav` helper + optional breadcrumb from route registry metadata |

### 3.2 Inventory

| Component | Path | Provides | Used on P1–P7? |
|-----------|------|----------|---------------|
| Navigation registry | `includes/m360-navigation-registry.php` | ~63 routes: `route_key`, `phase_code`, `title_fa`, `url`, category | **Catalog/audit only** — not runtime breadcrumbs |
| Staff Home workbench | `erp-staff-home.php`, `includes/m360-staff-home-helper.php` | Role cards → boards; logout topbar | **Entry only** — no upward link from boards |
| Product Home | `erp-product-home.php` | Module cards + `m360-rc-nav` | Owner/admin entry; not on staff boards |
| Route Map | `erp-route-map.php` | Full route table audit | Reference tool, not operational shell |
| P8 mgmt nav | `m360_mgmt_nav_links()` in `m360-management-kpi-helper.php` | Dashboard peer links | P8 pages only |
| Access mgmt nav | `m360_access_mgmt_nav()` | Sub-pages + «خانه محصول» | P11 access pages only |
| RC pill nav | `m360_nav_rc_links()` + `m360-release-hardening.css` | P10 release pages | Not on P1–P7 boards |
| Breadcrumb (demo) | `includes/moghare360-ui-shell.php` | Static shell breadcrumb | **Prototype only** |
| Ad-hoc back | Per-page CSS classes (`p3-back`, `m360-wx-back`, etc.) | One step to parent board/list | Detail pages only |
| Finance footer nav | `pricing_render_foot()` in `erp-pricing-engine.php` | Cross-links between finance pages | `erp-payment-tracking.php` etc. — no workbench link |

### 3.3 Login → landing flow

| Login | Landing | Constant / file |
|-------|---------|-----------------|
| Staff | `erp-staff-home.php` | `M360_STAFF_HOME_REDIRECT_PATH` |
| Owner | `erp-product-home.php` | `M360_OWNER_LOGIN_REDIRECT_PRIMARY` |

---

## 4. Existing Responsibility / Actor Capability Discovery

### 4.1 Standing rule applied

| Area | Exists? | Location | Status |
|------|---------|----------|--------|
| Actor user_id columns | **Yes** | `erp_jobcards`, domain tables, `*_events`, `*_change_history` | **Complete in DB** |
| Actor display in UI | **Partial** | Mostly raw IDs or absent | **Disconnected** |
| Requester | **Implicit** | Customer on online request / jobcard | Customer name shown; no «درخواست‌کننده» label |
| Approver | **Partial** | `erp_estimate_approvals`, `manager_release_*` on settlement | Customer OTP approval visible; internal approver rarely labeled |
| Performer / assignee | **Partial** | `assigned_technician_user_id`, `assigned_reception_user_id`, `qc_user_id` | **Stored; often not shown or shown as numeric ID** |
| Closer | **Partial** | `closed_by_user_id` on jobcard (P7) | **Not displayed on operational detail pages** |
| Last updated by | **Partial** | `updated_by_user_id`, `changed_by_user_id` in history | **Hidden** on m360 pages; raw ID on legacy Mission pages |
| Shared name resolver | **No** | Staff Home loads `core_users.full_name` for identity KPI only | Not reused on P1–P7 |

### 4.2 Domain helper patterns

Each P1–P7 domain helper exposes:

- Status constants + Persian labels (`M360_*_STATUS_LABELS_FA`)
- `m360_*_effective_status()` where applicable
- `m360_*_allowed_actions()` → drives action buttons
- History/event list functions (often rendered in detail page tables)

**No shared `m360_actor_display()` or responsibility header/footer helper exists.**

### 4.3 Exceptions (better responsibility UI)

| Page | Responsibility shown |
|------|---------------------|
| `erp-technical-jobcard-detail.php` | «تکنسین» = `assigned_technician_user_id` (numeric ID, not name) |
| `erp-crm-followup-detail.php` | «مسئول» = `assigned_to_text` (CRM only) |
| `erp-soft-run-finding-detail.php` | «شناسه مسئول» (soft-run pilot) |
| Legacy `erp-jobcard-detail.php`, `erp-service-operation-detail.php` | `changed_by_user_id` as raw ID in history table |
| `erp-purchase-request-detail.php` | `changed_by_user_id` in history (Mission 26) |

---

## 5. Existing Workflow Status / Next Action Discovery

### 5.1 Standing rule applied

| Capability | Exists? | Where | UI status |
|------------|---------|-------|-----------|
| Current status | **Yes** | Per-domain `*_status` on jobcard + document rows | **Shown** on detail pages (badges/KV grids) |
| Next required action (text) | **No** in P1–P7 | P8 `current_stage_label_fa`; rule engine `next_action` elsewhere | **Inferred from buttons**, not labeled |
| Return / عودت | **No Persian term** | QC uses `REWORK_REQUIRED` / «نیاز به Rework» | English-heavy |
| Approve / reject | **Yes** | Estimate customer approval; settlement manager release | Partial visibility |
| Perform / complete | **Yes** | Work execution, technical actions | Button-driven |
| Close | **Yes** | Settlement + `m360-jobcard-close-helper.php` | On settlement detail, not global strip |
| Blocked / waiting | **Yes** | Gate alerts (`$gateMessage`, `$gatesOk`) | Orange alerts when gates fail |
| Action history | **Yes** | `erp_jobcard_change_history`, domain `*_events` | Partial — some detail pages list history; timeline is P8 read-only |
| Pipeline stage | **Yes (P8)** | `m360_mgmt_resolve_stage()`, `M360_TIMELINE_MILESTONES_FA` | **Not on operational detail pages** |

### 5.2 Allowed-actions pattern (de facto “next action”)

| Helper function | Page |
|-----------------|------|
| `m360_reception_jobcard_allowed_actions()` | `erp-reception-jobcard-detail.php` |
| `m360_technician_workflow_allowed_actions()` | `erp-technical-jobcard-detail.php` |
| `m360_work_allowed_actions()` | `erp-work-execution-detail.php` |
| `m360_qc_allowed_actions()` | `erp-qc-detail.php` |
| `m360_fi_detail_default_actions()` / `m360_fi_allowed_actions()` | `erp-final-invoice-detail.php` |
| Settlement actions inline | `erp-settlement-detail.php` |
| Online request actions | `erp-reception-online-request-detail.php` |

Workflow logic is **complete in helpers**; UX guidance is **fragmented** — user must scan button section, not a status strip.

---

## 6. Operational Page Navigation Matrix

**Legend — Status:** ✅ present | ⚠️ partial | ❌ missing

| Page | Primary role | Back link | Main / workbench | Breadcrumb | Route context | Gap |
|------|--------------|-----------|------------------|------------|---------------|-----|
| **Reception** |
| `erp-reception-online-requests.php` | RECEPTION | ❌ | ❌ | ❌ | Filter pills only | Dead-end board |
| `erp-reception-online-request-detail.php` | RECEPTION | ✅ → list | ❌ | ❌ | ❌ | No workbench |
| `erp-reception-jobcards.php` | RECEPTION | ❌ | ❌ | ❌ | Status filters | Dead-end board |
| `erp-reception-jobcard-detail.php` | RECEPTION | ✅ → list | ❌ | ❌ | Contract summary | No assignee nav context |
| `erp-reception-jobcard-action.php` | RECEPTION | POST only | ❌ | ❌ | ❌ | Action endpoint |
| `erp-intake-contracts.php` | RECEPTION | ❌ | ❌ | ❌ | Cross-links to other boards | No Staff Home |
| `erp-intake-contract-detail.php` | RECEPTION | ✅ → contracts | ❌ | ❌ | ❌ | No workbench |
| **Technical / Service Manager** |
| `erp-technical-board.php` | TECH / SM | ❌ | ❌ | ❌ | Status filters | Dead-end board |
| `erp-technical-jobcard-detail.php` | TECH / SM | ✅ → board | ❌ | ❌ | Multi-status KV | Technician ID not name |
| `erp-technical-jobcard-action.php` | TECH | POST only | ❌ | ❌ | ❌ | Action endpoint |
| `erp-work-execution-board.php` | TECH / SM | ❌ | ❌ | ❌ | Status filters | Dead-end board |
| `erp-work-execution-detail.php` | TECH / SM | ✅ → board | ❌ | ❌ | Gate + status grid | No responsible person |
| `erp-work-execution-action.php` | TECH | POST only | ❌ | ❌ | ❌ | Action endpoint |
| `erp-jobcard-timeline.php` | OWNER / SM | ❌ | ❌ | ❌ | P8 mgmt nav + JobCard ID form | Not operational shell |
| **Parts / Inventory** |
| `erp-stock-board.php` | PARTS | ❌ | ❌ | ❌ | ❌ | No upward nav |
| `erp-part-reserve.php` | PARTS | ❌ | ❌ | ❌ | ❌ | No upward nav |
| `erp-jobcard-part-use.php` | PARTS / TECH | ❌ | ❌ | ❌ | JobCard context param | No workbench |
| `erp-jobcard-part-readonly-list.php` | PARTS | ❌ | ❌ | ❌ | ❌ | Read-only list |
| `erp-purchase-request-create.php` | PARTS | ❌ | ❌ | ❌ | ❌ | Form only |
| `erp-purchase-request-list.php` | PARTS | ⚠️ if exists | ❌ | ❌ | Backlog in Staff Home | Page may not exist |
| **Finance** |
| `erp-estimate-board.php` | FINANCE | ❌ | ❌ | ❌ | Status filters | Dead-end board |
| `erp-estimate-detail.php` | FINANCE | ✅ → board | ❌ | ❌ | Gates + status | No approver display |
| `erp-estimate-action.php` | FINANCE | POST only | ❌ | ❌ | ❌ | Action endpoint |
| `erp-payment-tracking.php` | FINANCE | ❌ | ❌ | ❌ | Finance footer cross-links | Legacy layout |
| `erp-final-invoice-board.php` | FINANCE | ❌ | ❌ | ❌ | Status filters | Dead-end board |
| `erp-final-invoice-detail.php` | FINANCE | ✅ → board | ❌ | ❌ | Cross-domain status strip | Best status coverage; no actors |
| `erp-final-invoice-action.php` | FINANCE | POST only | ❌ | ❌ | ❌ | Action endpoint |
| `erp-settlement-detail.php` | FINANCE | ✅ → invoice detail | ❌ | ❌ | Settlement + delivery actions | Back skips board |
| `erp-settlement-action.php` | FINANCE | POST only | ❌ | ❌ | ❌ | Action endpoint |
| **QC / Delivery** |
| `erp-qc-board.php` | QC / SM | ❌ | ❌ | ❌ | QC status filters | Dead-end board |
| `erp-qc-detail.php` | QC | ✅ → board | ❌ | ❌ | QC status + actions | No inspector name |
| `erp-qc-action.php` | QC | POST only | ❌ | ❌ | ❌ | Action endpoint |
| `erp-delivery-control.php` | QC | ❌ | ❌ | ❌ | Mission 30 standalone | No m360 shell |
| `customer-delivery-review.php` | Customer | Customer flow | ❌ | ❌ | OTP | Outside staff shell |
| `customer-delivery-sign.php` | Customer | Customer flow | ❌ | ❌ | OTP | Outside staff shell |
| **Staff / Management** |
| `erp-staff-home.php` | All staff | ❌ (hub) | ✅ self | ❌ | Role workbench | Forward-only |
| `erp-product-home.php` | OWNER | ❌ (hub) | ✅ self | ❌ | Module + RC nav | Not linked from P1–P7 |
| `erp-route-map.php` | OWNER | ❌ | ⚠️ RC nav | ❌ | Full catalog | Dev-oriented |
| `erp-management-dashboard.php` | OWNER | ❌ | ❌ | ❌ | P8 mgmt nav | Management layer |
| `erp-owner-control-center.php` | OWNER | ❌ | ❌ | ❌ | P8 mgmt nav | Read-only oversight |

**Confirmed:** Grep across P1–P7 operational PHP files finds **zero** references to `erp-staff-home.php`.

---

## 7. Document Responsibility Matrix

| Page / document type | Requester | Approver | Performer | Current responsible | DB fields available | Displayed now? | Gap |
|---------------------|-----------|----------|-----------|---------------------|---------------------|----------------|-----|
| Online request | Customer (implicit) | Reception staff (action) | — | — | `customer_id`; history `changed_by_user_id` | Customer name only | No «درخواست‌کننده» / actor labels |
| Reception JobCard | Customer | — | Reception | `assigned_reception_user_id` | + `created_by_user_id`, `reception_user_id` | Customer only | Assignee never shown |
| Intake contract | Customer sign | — | Reception | — | `created_by_user_id` on contract/events | Status/dates | No creator label |
| Technical JobCard | — | — | Technician | `assigned_technician_user_id` | + service op `assigned_to_user_id` | **Numeric ID** as «تکنسین» | No name lookup |
| Work execution | — | — | Technician | `assigned_technician_user_id`, `final_technician_user_id` | Same + event actors | **Not shown** | Full gap |
| Estimate | Customer (approver via OTP) | Customer + internal | — | — | `erp_estimate_approvals`, `created_by_user_id` | Status + customer flow | Internal creator/approver hidden |
| QC check | — | — | QC inspector | `qc_user_id`, `checked_by_user_id` | Item-level checkers | **Not shown** | Full gap |
| Final invoice | — | — | Finance | — | `created_by_user_id` | Status only | No creator |
| Settlement | — | Manager release | Finance | — | `manager_release_*`, `created_by_user_id` | Amounts + status | Approver not labeled |
| Customer delivery | Customer OTP | Customer | — | — | `confirmation_status` | Staff sees raw status codes | No actor names |
| JobCard close | — | Manager (release gate) | — | `closed_by_user_id` | P7 column | **Not on detail UI** | Close actor invisible |
| Purchase request | `requested_by_user_id` | `approved_by_user_id` | — | — | Mission 26 table | History shows user_id | Not integrated in m360 Staff flow |

---

## 8. Workflow Status / Next Action Matrix

| Page / document type | Current status shown? | Next action shown? | Return/approve/perform/close clear? | History/audit visible? | Gap |
|---------------------|----------------------|-------------------|-------------------------------------|------------------------|-----|
| Online request list/detail | ✅ `request_status` | ⚠️ Action buttons | Accept/reject clear | ⚠️ History in helper, limited UI | No next-action label |
| Reception JobCard | ✅ `jobcard_status` + contract | ⚠️ Allowed buttons | Contract gate alert | ⚠️ Change history partial | No pipeline stage |
| Technical board/detail | ✅ reception + technical status | ⚠️ Allowed buttons | Diagnosis/service ops | ⚠️ Events in detail | Technician ownership unclear |
| Work execution | ✅ execution + gates | ⚠️ Allowed buttons | Ready-for-QC clear | ✅ History + events sections | No single next step |
| Estimate | ✅ estimate + gates | ⚠️ Inline forms | Customer approve/reject | ⚠️ Events | Multi-step not summarized |
| QC | ✅ effective QC status | ⚠️ QC action buttons | Rework not «عودت» | ⚠️ Events | Terminology gap |
| Final invoice | ✅ **richest cross-status strip** | ⚠️ FI actions | Finalize/settle path | ⚠️ Limited | Best template for shell reuse |
| Settlement | ✅ settlement + delivery status | ✅ Release/close block | Manager release messaging | ⚠️ | No unified with other pages |
| Delivery control (M30) | ✅ `delivery_status` | Release button | Separate page | Mission 30 history | Not m360-integrated |
| P8 timeline | ✅ `current_stage_label_fa` | ⚠️ Stage link only | Milestones list | ✅ Aggregated events | Read-only; JobCard ID required |
| P8 dashboard | ✅ Pipeline columns | ❌ | Diagnostic only | ✅ | Not operational UX |

---

## 9. Existing DB Support Matrix

| Table | Relevant columns | Responsibility? | Workflow status? | Audit? | Notes |
|-------|------------------|-----------------|------------------|--------|-------|
| `erp_jobcards` | `jobcard_status`, `lifecycle_state`, `assigned_reception_user_id`, `assigned_technician_user_id`, `final_technician_user_id`, `qc_user_id`, `technical_status`, `estimate_status`, `work_execution_status`, `qc_status`, `final_invoice_status`, `settlement_status`, `customer_delivery_status`, `created_by_user_id`, `updated_by_user_id`, `closed_by_user_id`, gate columns | **Yes** | **Yes** | Via change history | Central document hub |
| `erp_jobcard_change_history` | `change_type`, `previous_status`, `new_status`, `changed_by_user_id` | **Yes** | **Yes** | **Yes** | Base: `mission_17_jobcard_foundation.sql` |
| `erp_customer_online_requests` + history | `request_status`; history: `event_type`, `changed_by_user_id` | Partial | **Yes** | **Yes** | P1 migration |
| `erp_intake_contracts` / events | `contract_status`, `created_by_user_id` | Partial | **Yes** | Events | P1.5 |
| `erp_estimates` / approvals / events | `estimate_status`, gates, `created_by_user_id` | Partial | **Yes** | Events | P4 |
| `erp_qc_checks` / items / events | `qc_status`, `checked_by_user_id`, `created_by_user_id` | **Yes** | **Yes** | Events + `erp_qc_check_history` | P6 + M30 base |
| `erp_final_invoices` | `invoice_status`, `created_by_user_id` | Partial | **Yes** | Events | P7 |
| `erp_settlement_controls` | `settlement_status`, `created_by_user_id`, manager release fields | Partial | **Yes** | Events | P7 |
| `erp_customer_delivery_confirmations` | `confirmation_status` | Customer actor | **Yes** | Delivery events | P7 |
| `erp_work_execution_events` | `created_by_user_id` | **Yes** | **Yes** | **Yes** | P5 |
| `erp_purchase_requests` | `request_status`, `requested_by_user_id`, `approved_by_user_id` | **Yes** | **Yes** | `erp_purchase_request_history` | M26 — parallel to m360 |
| `core_users` | `user_id`, `full_name`, `username` | **Display lookup** | — | — | Join target for names |
| `core_audit_logs` | `actor_user_id`, `action` | **Yes** | — | **Yes** | Access/platform audit |
| `vw_m360_owner_jobcard_pipeline` | All major status columns + stage resolution | — | **Yes** | View | P8 — read-only aggregation |

**Gaps in DB (not blocking UI display):**

- No unified `current_responsible_user_id` — responsibility is **phase-specific columns**  
- No `next_action_code` on jobcard — next action is **computed in PHP helpers**  
- No `completed_by_user_id` — use `final_technician_user_id` / event actors  
- «عودت» not modeled — QC rework statuses used instead  

---

## 10. Existing Helpers / UI Patterns

### 10.1 Existing Similar Capability Table

| Capability | Existing file/helper/table | Status | Reuse / Upgrade / Build | Risk |
|------------|---------------------------|--------|-------------------------|------|
| Post-login role workbench | `m360-staff-home-helper.php` | **Complete** | **Reuse** as «میز کار» target in shell | Low |
| Module catalog / owner home | `erp-product-home.php`, `m360_nav_rc_links()` | **Complete** | **Reuse** for admin back link | Low |
| Route catalog | `m360-navigation-registry.php` | **Complete** | **Upgrade** → breadcrumb metadata | Medium — registry URLs are dev-facing |
| One-step back links | Per-page CSS + anchor | **Partial** | **Upgrade** → standard shell component | Low |
| Breadcrumb UI | `moghare360-ui-shell.php` | **Prototype** | **Upgrade** wire to registry | Medium |
| Status labels | Each `m360-*-helper.php` | **Complete** | **Reuse** | Low |
| Allowed actions | `m360_*_allowed_actions()` | **Complete** | **Reuse** → derive next-action text | Low — wording must stay aligned with handlers |
| Pipeline stage | `m360_mgmt_resolve_stage()` | **Complete (P8)** | **Upgrade** expose on detail pages | Low — read-only |
| Event timeline | `m360-jobcard-timeline-helper.php` | **Complete** | **Reuse** link from detail shell | Low |
| Actor user IDs in DB | `erp_jobcards` + history | **Complete** | **Upgrade** display via `core_users` join | Low — no schema change |
| User name lookup | Staff Home context query only | **Partial** | **Build minimal** shared resolver helper | Low |
| Responsibility footer/header | — | **Missing** | **Build minimal** shell fragment | Medium UX |
| Next action text field | Rule engine / soft-run only | **Disconnected** | **Build** computed label from allowed_actions | Low |
| Document return (عودت) | QC rework codes | **Partial** | **Upgrade** Persian labels only | Low |

### 10.2 CSS / layout patterns

Operational pages share **soft-run release CSS** (`moghare360-soft-run-release.css`) plus domain CSS (`m360-work-execution.css`, `m360-qc.css`, `m360-estimate.css`, etc.). Detail pages use **two-column KV grids** — good anchor for a responsibility strip above the grid.

---

## 11. Missing or Fragmented Areas

1. **No upward navigation** from any P1–P7 board to Staff Home or Product Home.  
2. **No breadcrumb** on live operational pages (demo shell only).  
3. **No operational route context** (phase, board name, JobCard ID trail) except ad-hoc page titles.  
4. **Responsibility columns populated but not displayed** — especially `assigned_reception_user_id`, `qc_user_id`, `closed_by_user_id`.  
5. **User IDs shown instead of names** where assignee is shown (technical detail).  
6. **No unified status strip** — final invoice detail is closest but still no actors.  
7. **Next action not verbalized** — users must interpret button sets.  
8. **P8 pipeline model not reused** on operational detail pages.  
9. **Timeline / history fragmented** — available in helpers but not consistently linked from every detail page.  
10. **Parallel legacy pages** (Mission 17/20/26 detail pages) duplicate patterns without m360 integration.  
11. **Parts/finance standalone pages** (`erp-payment-tracking.php`, stock/reserve) have **zero** back/workbench links.  
12. **Persian workflow terms inconsistent** — «Rework» vs «عودت»; English status codes in some staff views.

Prior audit alignment: `MOGHARE360_P11_7_WORKBENCH_SCOPE_GATE_REPORT.md` explicitly deferred breadcrumbs on P1–P7 detail pages to backlog — **still accurate**.

---

## 12. Security and Workflow Risks

| Risk | Description | Severity |
|------|-------------|----------|
| User gets lost | Boards are dead-ends; browser back is only escape | **Medium UX** |
| Wrong actor assumption | Assignee not shown → staff assume wrong ownership | **Medium operational** |
| Wrong action click | Many buttons visible; no highlighted «required next step» | **Medium** |
| Manager cannot diagnose stuck doc | Must open P8 dashboard or timeline manually | **Medium** |
| Navigation ≠ authorization | Adding Staff Home links does not bypass guards (P11.8-A precedent) | **Low if UI-only** |
| Display-only changes safe | Showing existing `user_id` names does not expand permissions | **Low** |
| Workflow bypass | **Must not** add new actions in shell — display/navigation only | **High if violated** |

---

## 13. Reuse / Upgrade / Build Decision

| Area | Decision | Rationale |
|------|----------|-----------|
| Back to board/list | **Upgrade** | Keep existing links; add shell bar above |
| Back to Staff Home / Product Home | **Build minimal** | Link only — no new permissions |
| Breadcrumb | **Upgrade** | Derive from navigation registry + current page |
| Responsibility strip | **Build minimal** | Read existing columns + `core_users` join |
| Workflow status strip | **Upgrade** | Reuse domain status labels + P8 stage optional |
| Next action label | **Build minimal** | Compute from first allowed action or gate message — **no handler change** |
| Audit/history link | **Upgrade** | Link to existing timeline/history sections |
| New DB tables | **Do not build** | Not required for display phase |
| New permissions | **Do not build** | Not required for navigation/display |
| New workflow states | **Do not build** | Out of scope |

---

## 14. Minimum Next Patch Recommendation

### Minimum Patch Recommendation Table

| Area | Existing base? | Safe next action | Needs DB change? | Needs permission change? | Recommended phase |
|------|----------------|------------------|------------------|--------------------------|-------------------|
| Operational nav bar | Staff Home, back links, RC nav CSS | Add shared top bar: «بازگشت» + «میز کار» + optional «خانه محصول» (admin) | **No** | **No** | **P11.8-B** |
| Breadcrumb | Registry + demo shell | 2–3 level breadcrumb from registry metadata | **No** | **No** | P11.8-B or P11.8-C |
| User display resolver | `core_users` query in Staff Home | `m360_operational_user_label($userId)` helper | **No** | **No** | **P11.8-B** |
| Responsibility strip | Jobcard + domain columns | Show requester (customer), assignee, creator, last actor on detail pages | **No** | **No** | **P11.8-B** |
| Status + next action panel | `*_allowed_actions()`, status labels | Single panel: وضعیت فعلی + اولین اقدام مجاز + gate alert | **No** | **No** | **P11.8-B** |
| Pipeline stage on detail | `m360_mgmt_resolve_stage()` | Read-only «مرحله پرونده» + link to timeline | **No** | **No** | P11.8-C |
| Persian rework label | QC constants | Label map only — `REWORK_REQUIRED` → «نیاز به اصلاح / عودت» | **No** | **No** | P11.8-B polish |
| Unified history tab | Timeline helper | «تاریخچه» tab linking `erp-jobcard-timeline.php?jobcard_id=` | **No** | **No** | P11.8-C |
| Action handler changes | P1–P7 handlers | **Forbidden** in nav/responsibility phase | — | — | Not in P11.8-B |
| HR / impersonation / override | Backlog cards | Remain backlog | — | — | P15+ |

**Smallest controlled P11.8-B scope:** One shared helper + CSS include on **detail pages first** (where document responsibility matters most), then boards.

---

## 15. Final Persian Answers

**1. آیا الگوی بازگشت به صفحه قبل / صفحه اصلی در پروژه وجود دارد؟**  
بله — **جزئی و پراکنده**: بازگشت یک‌مرحله‌ای در صفحات جزئیات (`← بازگشت به برد/فهرست`) و هاب‌های Staff Home / Product Home وجود دارد؛ اما **الگوی واحد «بازگشت به میز کار / صفحه اصلی» روی صفحات عملیاتی P1–P7 وجود ندارد**.

**2. آیا الان صفحات عملیاتی مسیر برگشت و breadcrumb کافی دارند؟**  
**خیر.** بردها مسیر برگشت ندارند؛ جزئیات فقط به برد والد برمی‌گردند؛ breadcrumb فقط در نمونه shell دمو است، نه در صفحات زنده.

**3. آیا در اسناد عملیاتی مشخص است درخواست‌کننده کیست؟**  
**تا حدی.** نام مشتری نمایش داده می‌شود اما برچسب «درخواست‌کننده» و actor داخلی به‌صورت سیستماتیک نشان داده نمی‌شود.

**4. آیا مشخص است تأییدکننده کیست؟**  
**در برخی مسیرها جزئی.** تأیید مشتری در برآورد (OTP) وجود دارد؛ تأیید مدیر در تسویه (`manager_release`) در منطق هست ولی **به‌صورت برچسب انسانی تأییدکننده** روی UI عملیاتی کامل نیست.

**5. آیا مشخص است انجام‌دهنده یا مسئول فعلی کیست؟**  
**عمدتاً خیر.** شناسه‌های `assigned_*` و `qc_user_id` در DB هستند؛ UI یا نشان نمی‌دهد یا فقط **عدد user_id** (مثلاً تکنسین) نمایش می‌دهد — بدون نام پرسنل.

**6. آیا مشخص است سند باید عودت شود، تأیید شود، انجام شود یا بسته شود؟**  
**وضعیت بله، اقدام بعدی صریح خیر.** وضعیت‌های فازی و دکمه‌های مجاز وجود دارد؛ اما **برچسب واحد «اقدام بعدی»** و واژه «عودت» (به‌جای Rework) یکپارچه نیست. بستن پرونده mainly در صفحه تسویه دیده می‌شود.

**7. آیا دیتابیس فیلدهای کافی برای نمایش این اطلاعات دارد؟**  
**بله — برای نمایش read-only کافی است.** ستون‌های actor، status، history/events و view پipeline P8 موجود است.

**8. آیا این موضوع نیاز به DB جدید دارد یا با اطلاعات موجود قابل نمایش است؟**  
**با اطلاعات موجود قابل نمایش است** (join به `core_users` + استفاده از history موجود). DB جدید برای **نمایش** لازم نیست؛ فقط برای workflow جدید یا actor جدید لازم می‌شود.

**9. کمترین Patch بعدی چیست؟**  
**P11.8-B:** نوار ناوبری عملیاتی مشترک (بازگشت + میز کار) + نوار مسئولیت/وضعیت read-only روی صفحات جزئیات JobCard — بدون تغییر Auth، permission، schema، یا handler.

**10. چه چیزهایی باید برای فازهای بعدی بماند؟**  
- تغییر state machine / handlerهای P1–P7  
- permission یا role جدید  
- impersonation / act-as-staff  
- HR self-service (P15)  
- breadcrumb کامل روی همه بردها (می‌تواند فاز C باشد)  
- یکپارچه‌سازی Mission legacy pages  
- audit taxonomy مدیریتی formal (اختیاری)

---

P11.8-B-0 discovers whether MOGHARE360 already has reusable navigation, responsibility, actor, workflow-status, next-action and audit structures before any operational page shell or document responsibility display is built.
