# MOGHARE360 — Database Gap Matrix

**Document ID:** CANONICAL-002  
**Status:** EXECUTION AUTHORITY — Gap analysis only (no SQL)  
**Database:** `moghare360_ERP` on SQL Server  
**Baseline:** 96 tables, 77 FKs, 1224 columns (Phase 02–04 docs)  
**Sources:** `docs/database/MOGHARE360_DATABASE_DOMAIN_TABLE_MAP.md`, P1–P10 migrations, audit freeze reports

**Rules:** Do not create SQL. Do not run SQL. Owner approval required before any schema change.

---

## Summary Statistics

| Metric | Value | Implication |
|--------|------:|-------------|
| Total tables | 96 | Foundation exists |
| Empty operational tables | 46 | Structure ahead of live usage |
| FK disabled/untrusted | 0 | Relational integrity active |
| ID type mismatches | 52 candidates | Join risk across phases |
| Duplicate domain candidates | 63 heuristic | Needs owner domain map |
| Payload overuse (intake) | High | Cartable, wizard state in JSON |

---

## Module Gap Matrix

**Status legend:** COMPLETE | PARTIAL | MISSING | NEEDS REDESIGN | NEEDS OWNER DECISION

**Effort legend:** S = small (days), M = medium (1–2 weeks), L = large (3+ weeks), XL = major program

---

### 1. Core Access / Auth

| Field | Assessment |
|-------|------------|
| **Status** | **PARTIAL** |
| **Existing tables** | `core_users`, `core_roles`, `core_permissions`, `core_role_permissions`, `core_user_roles`, `core_staff_profiles`, `core_departments`, `core_positions`, `core_access_*`, `core_audit_logs` (16 tables) |
| **Missing tables** | None critical for V1; SaaS tenant tables N/A |
| **Weak areas** | Runtime frozen — no schema change planned; access UI (P11.4) uses existing tables |
| **Payload overuse** | No |
| **Replace payload later?** | N/A |
| **DB change required?** | No for intake stabilization |
| **Owner approval?** | Yes for any permission seed change |
| **Effort** | M (operational hardening only) |

---

### 2. Customer

| Field | Assessment |
|-------|------------|
| **Status** | **PARTIAL** |
| **Existing tables** | `erp_customers`, `erp_customer_phones`, `erp_customer_core_history`, `erp_customer_intakes` |
| **Missing tables** | Customer cartable task table (deferred — payload bridge in V1) |
| **Weak areas** | Duplicate relation paths (`erp_customer_vehicle_relations` vs bindings); empty rows in ops |
| **Payload overuse** | Online request stores form echo in `request_payload_json` |
| **Replace payload later?** | **Yes** — cartable should become real table when portal approved |
| **DB change required?** | Later — `erp_customer_cartable_tasks` proposed post-UAT |
| **Owner approval?** | **Yes** |
| **Effort** | M |

---

### 3. Vehicle

| Field | Assessment |
|-------|------------|
| **Status** | **PARTIAL** |
| **Existing tables** | `erp_vehicles`, `erp_vehicle_photo_records`, `erp_customer_vehicle_bindings`, `erp_customer_vehicle_relations`, `erp_customer_vehicle_change_history` |
| **Missing tables** | `erp_vehicle_brands`, `erp_vehicle_models` (owner allowlist enforcement) |
| **Weak areas** | Brand/model free-text; no DB-enforced allowlist for Toyota/Lexus/Kia/Hyundai/BYD/Lucano/Chery |
| **Payload overuse** | Vehicle fields duplicated in intake JSON |
| **Replace payload later?** | **Yes** — canonical vehicle on `erp_vehicles` should be sole truth after intake lock |
| **DB change required?** | **Yes** — brand/model master tables |
| **Owner approval?** | **Yes** |
| **Effort** | M |

---

### 4. Online Request

| Field | Assessment |
|-------|------------|
| **Status** | **PARTIAL** |
| **Existing tables** | `erp_customer_online_requests`, `erp_customer_online_request_history` (P1 migration) |
| **Missing tables** | None for core flow |
| **Weak areas** | `request_payload_json` carries wizard + cartable + OTP echo; live data inconsistent with tests |
| **Payload overuse** | **Critical** — entire intake wizard state in JSON |
| **Replace payload later?** | **Partial** — structured columns for status; keep JSON for extensibility |
| **DB change required?** | Optional normalization columns post-intake UAT |
| **Owner approval?** | Yes for column adds |
| **Effort** | S–M |

---

### 5. OTP

| Field | Assessment |
|-------|------------|
| **Status** | **PARTIAL** |
| **Existing tables** | Legacy `otp_verifications` (MySQL-era references in deprecated routes); session-based OTP in PHP (no dedicated ERP table for canonical flow) |
| **Missing tables** | Optional `erp_otp_audit_log` for enterprise audit |
| **Weak areas** | No SQL persistence of OTP state in canonical path; config in private file |
| **Payload overuse** | `otp_verified` flag in request row + payload |
| **Replace payload later?** | OTP verified should remain on request row |
| **DB change required?** | Optional audit table only |
| **Owner approval?** | Yes for audit table |
| **Effort** | S |

---

### 6. Reception Intake

| Field | Assessment |
|-------|------------|
| **Status** | **PARTIAL** |
| **Existing tables** | `erp_customer_online_requests`, `erp_customer_intakes`, payload JSON paths |
| **Missing tables** | `erp_reception_intake_sections` (normalized section completion) — optional |
| **Weak areas** | Browser UAT failing; section truth in JSON not SQL |
| **Payload overuse** | **Critical** |
| **Replace payload later?** | **Yes** for cartable, photos metadata, contract mirrors |
| **DB change required?** | After browser UAT — phased normalization |
| **Owner approval?** | **Yes** |
| **Effort** | L |

---

### 7. Legal Contracts

| Field | Assessment |
|-------|------------|
| **Status** | **PARTIAL** |
| **Existing tables** | `erp_intake_contracts`, `erp_intake_contract_signatures`, `erp_intake_contract_events` (P1.5); `erp_customer_contracts`, `erp_customer_contract_acceptances` |
| **Missing tables** | Unified contract registry linking intake + JobCard |
| **Weak areas** | Two contract paths (P1.5 JobCard vs intake payload cartable) |
| **Payload overuse** | E7D mirrors contract status in JSON |
| **Replace payload later?** | **Yes** — DB should own contract state |
| **DB change required?** | **NEEDS REDESIGN** — merge intake cartable into contract tables |
| **Owner approval?** | **Yes** |
| **Effort** | L |

---

### 8. Customer Cartable

| Field | Assessment |
|-------|------------|
| **Status** | **MISSING** (DB) / **PARTIAL** (payload bridge) |
| **Existing tables** | None dedicated |
| **Missing tables** | `erp_customer_cartable_tasks`, `erp_customer_cartable_events` |
| **Weak areas** | Entire task in `request_payload_json.customer_cartable` |
| **Payload overuse** | **100%** |
| **Replace payload later?** | **Yes — mandatory for production** |
| **DB change required?** | **Yes** |
| **Owner approval?** | **Yes** |
| **Effort** | M |

---

### 9. JobCard

| Field | Assessment |
|-------|------------|
| **Status** | **PARTIAL** |
| **Existing tables** | `erp_jobcards`, `erp_jobcard_change_history`, `erp_jobcard_part_usage`, `erp_jobcard_part_usage_history`, `erp_jobcard_cost_headers`, `erp_jobcard_cost_lines` |
| **Missing tables** | Intake-to-JobCard conversion audit (optional) |
| **Weak areas** | Conversion from intake not implemented (C-2D forbidden); many JobCard UI pages, unclear canonical path |
| **Payload overuse** | JobCard snapshot fields |
| **Replace payload later?** | JobCard row is primary truth |
| **DB change required?** | Minor — conversion linkage columns exist on online_requests |
| **Owner approval?** | **Yes** for C-2D conversion logic |
| **Effort** | M (conversion) + L (UI consolidation) |

---

### 10. Internal Repair

| Field | Assessment |
|-------|------------|
| **Status** | **PARTIAL** |
| **Existing tables** | `erp_service_operations`, `erp_operation_service_steps`, `erp_operation_history`, `erp_service_operation_change_history` |
| **Missing tables** | Workshop bay assignment (may be in payload) |
| **Weak areas** | 46 empty tables include operation tables; P3 workflow exists in code |
| **Payload overuse** | Some step state |
| **DB change required?** | Minor |
| **Owner approval?** | Yes |
| **Effort** | M |

---

### 11. External Repair

| Field | Assessment |
|-------|------------|
| **Status** | **MISSING** |
| **Existing tables** | No dedicated external repair table identified |
| **Missing tables** | `erp_external_repair_orders`, vendor link |
| **Weak areas** | Not in P1–P10 migrations |
| **Payload overuse** | Unknown |
| **DB change required?** | **Yes** |
| **Owner approval?** | **Yes** |
| **Effort** | L |

---

### 12. Estimate

| Field | Assessment |
|-------|------------|
| **Status** | **PARTIAL** |
| **Existing tables** | P4 estimate tables (via migration); `erp_service_approval_requests` |
| **Missing tables** | Customer estimate sign-off linkage |
| **Weak areas** | Empty operational rows |
| **DB change required?** | Minor |
| **Owner approval?** | Yes |
| **Effort** | M |

---

### 13. Parts Usage

| Field | Assessment |
|-------|------------|
| **Status** | **PARTIAL** |
| **Existing tables** | `erp_jobcard_part_usage`, `erp_part_reservations`, `erp_parts` |
| **Missing tables** | None core |
| **Weak areas** | Empty usage rows in dev DB |
| **DB change required?** | No |
| **Effort** | S |

---

### 14. Inventory

| Field | Assessment |
|-------|------------|
| **Status** | **PARTIAL** |
| **Existing tables** | `erp_inventory_items`, `erp_inventory_stock_movements`, `erp_inventory_purchase_*`, `erp_stock_balances`, `erp_stock_movements`, `erp_parts` |
| **Missing tables** | Full warehouse workflow tables may be incomplete |
| **Weak areas** | Duplicate paths (`erp_inventory_*` vs `erp_stock_*`); NEEDS OWNER DECISION on canonical |
| **DB change required?** | **NEEDS REDESIGN** — deduplicate domains |
| **Owner approval?** | **Yes** |
| **Effort** | L |

---

### 15. Stock Locations

| Field | Assessment |
|-------|------------|
| **Status** | **PARTIAL** |
| **Existing tables** | `erp_stock_locations` |
| **Missing tables** | Bin/shelf granularity |
| **DB change required?** | Optional |
| **Effort** | S |

---

### 16. Domestic Purchase

| Field | Assessment |
|-------|------------|
| **Status** | **PARTIAL** |
| **Existing tables** | `erp_purchase_requests`, `erp_purchase_request_history`, `erp_suppliers`, `erp_inventory_purchase_requests` |
| **Missing tables** | PO, GRN, invoice matching |
| **Weak areas** | Preview-level only |
| **DB change required?** | **Yes** for full purchase cycle |
| **Owner approval?** | **Yes** |
| **Effort** | L |

---

### 17. Foreign Import

| Field | Assessment |
|-------|------------|
| **Status** | **MISSING** |
| **Existing tables** | None dedicated |
| **Missing tables** | Import orders, customs, FX, landed cost |
| **DB change required?** | **Yes** |
| **Owner approval?** | **Yes** |
| **Effort** | XL |

---

### 18. Logistics

| Field | Assessment |
|-------|------------|
| **Status** | **MISSING** |
| **Existing tables** | Partial via stock movements |
| **Missing tables** | Shipments, carriers, tracking |
| **DB change required?** | **Yes** |
| **Owner approval?** | **Yes** |
| **Effort** | L |

---

### 19. Service Sales

| Field | Assessment |
|-------|------------|
| **Status** | **PARTIAL** |
| **Existing tables** | `erp_finance_service_price_list`, service lines on invoices |
| **Missing tables** | Standalone service sales orders |
| **DB change required?** | M |
| **Effort** | M |

---

### 20. Parts Sales

| Field | Assessment |
|-------|------------|
| **Status** | **MISSING** |
| **Existing tables** | Parts master only |
| **Missing tables** | Counter sales, POS-style orders |
| **DB change required?** | **Yes** |
| **Owner approval?** | **Yes** |
| **Effort** | L |

---

### 21. Accounting

| Field | Assessment |
|-------|------------|
| **Status** | **MISSING** (full ledger) / **PARTIAL** (preview) |
| **Existing tables** | `erp_payments`, `erp_payment_records`, `erp_financial_summary_snapshots`, cost headers/lines — **preview only** |
| **Missing tables** | Chart of accounts, journal entries, ledger, fiscal periods |
| **Weak areas** | Master prompt forbids official accounting claim |
| **DB change required?** | **Yes — major** |
| **Owner approval?** | **Yes** |
| **Effort** | XL |

---

### 22. Audit (Financial / Operational)

| Field | Assessment |
|-------|------------|
| **Status** | **PARTIAL** |
| **Existing tables** | `core_audit_logs`, module `*_history` tables across domains |
| **Missing tables** | Unified audit query views; tamper-evident store |
| **DB change required?** | Optional hardening |
| **Effort** | M |

---

### 23. HR

| Field | Assessment |
|-------|------------|
| **Status** | **PARTIAL** |
| **Existing tables** | `erp_hr_employees`, `erp_hr_attendance_records`, `erp_hr_employment_contracts`, `erp_hr_disciplinary_records`, `erp_hr_training_records`, `erp_hr_payroll_previews`, `erp_hr_history` |
| **Missing tables** | Official payroll integration |
| **Weak areas** | Payroll is preview-only |
| **DB change required?** | Yes for production payroll |
| **Owner approval?** | **Yes** |
| **Effort** | L |

---

### 24. Administration

| Field | Assessment |
|-------|------------|
| **Status** | **PARTIAL** |
| **Existing tables** | Core access + company user tables |
| **Missing tables** | Company settings, feature flags |
| **DB change required?** | Minor |
| **Effort** | S |

---

### 25. QC

| Field | Assessment |
|-------|------------|
| **Status** | **PARTIAL** |
| **Existing tables** | `erp_qc_checks`, `erp_qc_check_items`, `erp_qc_check_history`, `erp_operation_qc_decisions` |
| **Missing tables** | None core (P6 migration) |
| **Weak areas** | Empty rows; browser path unclear |
| **DB change required?** | No |
| **Effort** | M (runtime/UAT) |

---

### 26. Delivery / Release

| Field | Assessment |
|-------|------------|
| **Status** | **PARTIAL** |
| **Existing tables** | `erp_delivery_controls`, `erp_delivery_control_history`, `erp_final_invoices`, `erp_final_invoice_items`, `erp_settlement_controls` |
| **Missing tables** | None core (P7) |
| **Weak areas** | Customer delivery OTP pages exist; full browser UAT pending |
| **DB change required?** | No |
| **Effort** | M |

---

### 27. CRM / Follow-up

| Field | Assessment |
|-------|------------|
| **Status** | **PARTIAL** |
| **Existing tables** | `erp_crm_followup_records`, `erp_crm_followup_schedules`, `erp_crm_history`, `erp_customer_satisfaction_surveys`, `erp_customer_score_cards`, `erp_upsell_opportunities` |
| **Missing tables** | None core |
| **Weak areas** | Empty; not wired to delivery completion |
| **DB change required?** | No |
| **Effort** | M |

---

### 28. Management Dashboard

| Field | Assessment |
|-------|------------|
| **Status** | **PARTIAL** |
| **Existing tables** | P8 views/indexes on jobcards; soft run tables (P9) |
| **Missing tables** | Real-time KPI materialized views |
| **DB change required?** | Optional |
| **Effort** | M |

---

### 29. Security / Audit Logs

| Field | Assessment |
|-------|------------|
| **Status** | **PARTIAL** |
| **Existing tables** | `core_audit_logs`, access change history |
| **Missing tables** | Security event correlation |
| **DB change required?** | Optional |
| **Effort** | S |

---

### 30. Deployment / Device Access

| Field | Assessment |
|-------|------------|
| **Status** | **MISSING** (DB layer) |
| **Existing tables** | None — deployment is file/config layer |
| **Missing tables** | Optional device registration for APK/PWA |
| **DB change required?** | Future |
| **Owner approval?** | **Yes** |
| **Effort** | L (9-month milestone) |

---

## Cross-Cutting DB Risks

| Risk | Severity | Action |
|------|----------|--------|
| Payload as database | **Critical** | Phased normalization after intake UAT |
| 46 empty tables | Medium | Populate via real workflows, not seed fiction |
| ID type int/bigint mix | High | Alignment plan before cross-module reports |
| Duplicate inventory domains | High | Owner picks canonical (`erp_stock_*` vs `erp_inventory_*`) |
| No vehicle brand master | High | Add allowlist tables with owner approval |

---

## SQL Change Gate (Locked)

1. No SQL file creation without entry in this matrix updated.
2. Full proposal document: tables, columns, FKs, migration idempotency, rollback.
3. Owner written approval.
4. Browser UAT plan for affected module.
5. No destructive DDL.

---

**END OF DATABASE GAP MATRIX**
