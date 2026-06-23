# MOGHARE360 — Domain Ownership Map

**Database:** MOGHARE360_ERP  
**Schema:** dbo  
**Tables:** 96  
**Source:** Phase 02 baseline + Phase 05 SSMS domain ownership discovery  
**Status:** Proposed ownership — Documentation only

---

## Database Confirmation

| Property | Value |
|----------|-------|
| SSMS database name | moghare360_ERP |
| Official documentation name | **MOGHARE360_ERP** |
| Server | DESKTOP-U1P34B8\SQLEXPRESS |
| Discovery checked at | 2026-06-23 21:00:03.163 |

---

## Domain Ownership Rule

**Final owner must be determined by business function, not only table-name substring.**

Phase 04 heuristic duplicate detection flagged 63 candidates with false positives (e.g. `core_departments` → Part). Phase 05 assigns owners by **operational responsibility** and **workflow authority**.

| Principle | Application |
|-----------|-------------|
| Master entity | Domain that creates/owns lifecycle |
| History / audit row | Audit / History unless operationally domain-owned |
| Cross-domain link | Primary controller domain + FK review |
| Preview / pilot | Reporting / Soft Run / Commercial unless operational |

---

## 1. Identity / Access / Security (15 tables)

**Owns:** core access, users, roles, permissions, staff profile, departments, positions

| Table | Role |
|-------|------|
| `core_access_approval_rules` | Access approval rules |
| `core_access_approvals` | Approved access changes |
| `core_access_request_items` | Access request line items |
| `core_access_requests` | Access change requests |
| `core_access_restrictions` | Active restrictions |
| `core_access_suspensions` | Suspensions |
| `core_departments` | Organizational departments |
| `core_permissions` | Permission registry |
| `core_positions` | Job positions |
| `core_role_permissions` | Role–permission map |
| `core_roles` | Role definitions |
| `core_staff_profiles` | Staff profiles |
| `core_user_roles` | User–role assignment |
| `core_users` | User accounts |

*Phase 05 SSMS groups 15 tables under Identity / Access / Security (includes access reference data; `core_audit_logs` write-ownership remains Audit / History).*

> **Note:** `core_departments` is **Identity / Access / Security**, not Part/Inventory (substring false positive).

---

## 2. Audit / History (20 tables)

**Owns:** history/audit records unless table is domain-owned operationally

| Table | Notes |
|-------|-------|
| `core_audit_logs` | Cross-cutting security audit |
| `core_access_change_history` | Access change history |
| `erp_customer_core_history` | Customer change history |
| `erp_customer_vehicle_change_history` | Binding change history — *ambiguous; see review* |
| `erp_delivery_control_history` | Delivery history |
| `erp_jobcard_change_history` | Job card history |
| `erp_jobcard_part_usage_history` | Part usage history — *ambiguous; see review* |
| `erp_operation_history` | Operation history |
| `erp_qc_check_history` | QC history |
| `erp_service_operation_change_history` | Service operation history |
| `erp_inventory_purchase_history` | Inventory purchase history |
| `erp_purchase_request_history` | Purchase request history |
| `erp_finance_history` | Finance module history |
| `erp_payment_history` | Payment history |
| `erp_crm_history` | CRM history |
| `erp_hr_history` | HR history |
| `erp_rule_audit_history` | Rule engine audit |
| `erp_management_report_history` | Report run history |
| `erp_commercial_release_history` | Commercial release history |
| `erp_soft_run_pilot_history` | Soft-run pilot history |

---

## 3. CRM / Customer Experience (3 tables)

**Owns:** follow-up and upsell engagement (not customer master)

| Table | Role |
|-------|------|
| `erp_crm_followup_records` | Follow-up records |
| `erp_crm_followup_schedules` | Scheduled follow-ups |
| `erp_upsell_opportunities` | Upsell tracking |

---

## 4. Customer (9 tables)

**Owns:** customer master, intake, phones, satisfaction, score, customer–vehicle relations, service contracts

| Table | Role |
|-------|------|
| `erp_customers` | Customer master |
| `erp_customer_intakes` | Intake sessions |
| `erp_customer_phones` | Phone numbers |
| `erp_customer_vehicle_bindings` | Active bindings |
| `erp_customer_vehicle_relations` | Relations |
| `erp_customer_contracts` | **Service** contracts (not HR employment) |
| `erp_customer_contract_acceptances` | Contract acceptances |
| `erp_customer_satisfaction_surveys` | Satisfaction |
| `erp_customer_score_cards` | Customer scoring |

---

## 5. Vehicle (2 tables)

**Owns:** vehicle master and vehicle media/photo records

| Table | Role |
|-------|------|
| `erp_vehicles` | Vehicle master |
| `erp_vehicle_photo_records` | Vehicle photos |

---

## 6. JobCard (4 tables)

**Owns:** job card master, cost headers/lines, job card part usage where job card process is controlling

| Table | Role |
|-------|------|
| `erp_jobcards` | Job card master |
| `erp_jobcard_part_usage` | Parts on job — *ambiguous with Inventory; JobCard controls when tied to active job* |
| `erp_jobcard_cost_headers` | Cost header |
| `erp_jobcard_cost_lines` | Cost lines |

---

## 7. Operation / Service / QC / Delivery (8 tables)

**Owns:** service operations, delivery control, QC, service approvals

| Table | Role |
|-------|------|
| `erp_service_operations` | Service operations |
| `erp_operation_cases` | Operation cases |
| `erp_operation_service_steps` | Service steps |
| `erp_operation_qc_decisions` | QC decisions |
| `erp_operation_delivery_checks` | Delivery checks |
| `erp_qc_checks` | QC checks |
| `erp_delivery_controls` | Delivery controls |
| `erp_service_approval_requests` | Service approval requests |

---

## 8. Inventory / Parts / Purchase (11 tables)

**Owns:** parts, stock, inventory, suppliers, purchase requests

| Table | Role |
|-------|------|
| `erp_parts` | Parts master |
| `erp_suppliers` | Suppliers |
| `erp_stock_locations` | Locations |
| `erp_stock_balances` | Balances |
| `erp_stock_movements` | Movements |
| `erp_part_reservations` | Reservations |
| `erp_inventory_items` | Inventory items |
| `erp_inventory_stock_movements` | Inventory movements |
| `erp_inventory_rule_requests` | Rule-based requests |
| `erp_inventory_purchase_requests` | Inventory purchase requests |
| `erp_purchase_requests` | Purchase requests |

---

## 9. Finance Preview / Payment (7 tables)

**Owns:** preview finance, invoice preview, payment records — **not official accounting**

| Table | Role |
|-------|------|
| `erp_finance_labour_rates` | Labour rates |
| `erp_finance_part_margin_rules` | Part margin rules — *ambiguous with Inventory/Finance* |
| `erp_finance_service_price_list` | Price list |
| `erp_financial_summary_snapshots` | Summary snapshots |
| `erp_invoice_previews` | Invoice previews |
| `erp_payments` | Payment master |
| `erp_payment_records` | Payment detail |

---

## 10. HR (6 tables)

**Owns:** employees, attendance, payroll previews, training, disciplinary, **employment contracts**

| Table | Role |
|-------|------|
| `erp_hr_employees` | Employees |
| `erp_hr_employment_contracts` | **HR employment** contracts — not customer service contracts |
| `erp_hr_attendance_records` | Attendance |
| `erp_hr_payroll_previews` | Payroll preview |
| `erp_hr_training_records` | Training |
| `erp_hr_disciplinary_records` | Disciplinary |

> **Note:** `erp_hr_employment_contracts` is **HR**, not Customer Contract (substring false positive).

---

## 11. Rule / Workflow Decision (2 tables)

**Owns:** rule definitions and decisions

| Table | Role |
|-------|------|
| `erp_rule_definitions` | Rule definitions |
| `erp_rule_decisions` | Rule decisions |

---

## 12. Reporting / Soft Run / Commercial (9 tables)

**Owns:** demo, package, license, readiness, pilot, KPI/reporting tables

| Table | Role |
|-------|------|
| `erp_business_kpi_snapshots` | KPI snapshots |
| `erp_commercial_demo_registry` | Demo registry |
| `erp_commercial_package_plans` | Package plans |
| `erp_commercial_readiness_checks` | Readiness checks |
| `erp_license_preview_models` | License previews |
| `erp_soft_run_audit_checks` | Soft-run audits |
| `erp_soft_run_pilot_feedback` | Pilot feedback |
| `erp_soft_run_pilot_flow_snapshots` | Flow snapshots |
| `erp_soft_run_pilots` | Pilot sessions |

---

## Domain Totals

| Domain | Tables |
|--------|--------|
| Identity / Access / Security | 15 |
| Audit / History | 20 |
| CRM / Customer Experience | 3 |
| Customer | 9 |
| Vehicle | 2 |
| JobCard | 4 |
| Operation / Service / QC / Delivery | 8 |
| Inventory / Parts / Purchase | 11 |
| Finance Preview / Payment | 7 |
| HR | 6 |
| Rule / Workflow Decision | 2 |
| Reporting / Soft Run / Commercial | 9 |
| **Total** | **96** |

---

## Product Boundary

- Proposed ownership — owner approval required before SQL
- No official accounting activation
- No payment gateway/billing/tax integration created

---

**END OF DOMAIN OWNERSHIP MAP**
