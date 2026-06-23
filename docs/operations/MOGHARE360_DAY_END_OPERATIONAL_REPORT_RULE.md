# MOGHARE360 — Day-End Operational Report Rule

**Status:** PLANNED_NOT_IMPLEMENTED  
**SQL:** No SQL required

---

## Purpose

The **day-end operational report** gives owner/admin a single daily snapshot of workshop live run health — throughput, blocks, and follow-ups — without activating SaaS, portal, accounting, or payment gateway.

Prepared at end of each live run business day.

---

## Report Audience

| Role | Action |
|------|--------|
| Owner/admin | Review and sign off |
| CRM/admin | Prepare draft from ERP queries (future) or manual tally during pilot |
| Manager | Resolve open blocks listed |

---

## Daily Report Sections

### 1. Vehicles Received

| Metric | Description |
|--------|-------------|
| Count | New intakes today |
| List summary | Plate / customer name (internal report only) |

### 2. JobCards Opened

| Metric | Description |
|--------|-------------|
| Count | New JobCards created today |
| By type | Service category breakdown |

### 3. JobCards In Progress

| Metric | Description |
|--------|-------------|
| Count | Active not delivered |
| By stage | DRAFT, SUBMITTED, APPROVED, APPLIED |

### 4. JobCards Blocked

| Metric | Description |
|--------|-------------|
| Count | Cannot proceed |
| Block reasons | Validation, workflow, contract, media, parts |

### 5. JobCards Delivered

| Metric | Description |
|--------|-------------|
| Count | CLOSED today |
| QC pass rate | Pass / rework count |

### 6. QC Issues

| Metric | Description |
|--------|-------------|
| Fails | QC fail count |
| Reworks | Open rework jobs |
| Repeat QC | Jobs re-inspected |

### 7. Media Missing

| Metric | Description |
|--------|-------------|
| Incomplete input (6) | JobCards missing INPUT set |
| Incomplete output (8) | JobCards missing OUTPUT set |
| Diagnostic gaps | Missing Initial/Secondary/Final when required |

### 8. Contract / Approval Blocks

| Metric | Description |
|--------|-------------|
| Pending out-of-contract | Awaiting approval |
| Ceiling breaches blocked | Count |
| Missing acceptance | Count |

### 9. Inventory / Parts Blocks

| Metric | Description |
|--------|-------------|
| Reservation failures | Stock unavailable |
| PR pending | Purchase requests blocking work |

*Detail rules completed in Phase 21.*

### 10. Payment Tracking Preview

| Metric | Description |
|--------|-------------|
| Preview unpaid | Jobs delivered with preview unpaid flag |
| Preview paid note | Manual payment note recorded |
| **No official accounting** | Report label: preview only |

### 11. Manual Fallback Cases

| Metric | Description |
|--------|-------------|
| Fallback activations | Count |
| Paper IDs pending ERP entry | List |
| Backfill completed | Count |

### 12. Unresolved Errors

| Metric | Description |
|--------|-------------|
| From daily error log | Open / deferred items |
| Critical | Highlighted |

### 13. Next-Day Action List

| Item | Owner assignee |
|------|----------------|
| Blocked JobCards to clear | Name |
| Fallback backfill | Name |
| Training needs | Topic |
| Server/network follow-up | If applicable |

---

## Report Timing

| Rule | Requirement |
|------|-------------|
| Cutoff | End of workshop business day |
| Submission | To owner before close of admin shift |
| Sign-off | Owner acknowledges report |

---

## Data Sources (Future)

| Source | Module |
|--------|--------|
| MOGHARE360_ERP queries | JobCard, contract, media index |
| Daily error log | Operations |
| Manual tally | Pilot only if ERP reports not implemented |

**No new runtime dashboards in Phase 20.**

---

## Implementation Status

**PLANNED_NOT_IMPLEMENTED**

---

**END OF DAY-END OPERATIONAL REPORT RULE**
