# MOGHARE360 — Database Domain Table Map

**Database:** MOGHARE360_ERP  
**Schema:** dbo  
**Detected tables:** 96  
**Detected columns:** 1224  
**Source:** User-provided SSMS inventory  
**Status:** Documentation only

---

## Overview

This document maps all 96 detected `dbo` tables into nine business domains. Use this map before any future schema work to avoid duplicate entity creation.

---

## 1. Identity / Access / Security

**Table count:** 16

| Table | Typical role |
|-------|----------------|
| `core_access_approval_rules` | Approval rule definitions for access changes |
| `core_access_approvals` | Approved access change records |
| `core_access_change_history` | History of access modifications |
| `core_access_request_items` | Line items on access requests |
| `core_access_requests` | Staff access change requests |
| `core_access_restrictions` | Active access restrictions |
| `core_access_suspensions` | Suspended access records |
| `core_audit_logs` | Cross-cutting security/audit log |
| `core_departments` | Organizational departments |
| `core_permissions` | Permission key registry |
| `core_positions` | Job positions |
| `core_role_permissions` | Role-to-permission mapping |
| `core_roles` | Role definitions |
| `core_staff_profiles` | Staff profile extensions |
| `core_user_roles` | User-to-role assignment |
| `core_users` | User accounts |

---

## 2. Customer / Intake / Contract / Vehicle

**Table count:** 11

| Table | Typical role |
|-------|----------------|
| `erp_customer_contract_acceptances` | Contract acceptance records |
| `erp_customer_contracts` | Customer service contracts |
| `erp_customer_core_history` | Customer change history |
| `erp_customer_intakes` | Customer intake sessions |
| `erp_customer_phones` | Customer phone numbers |
| `erp_customer_vehicle_bindings` | Active customer–vehicle links |
| `erp_customer_vehicle_change_history` | Vehicle binding history |
| `erp_customer_vehicle_relations` | Customer–vehicle relations |
| `erp_customers` | Customer master |
| `erp_vehicle_photo_records` | Vehicle photo metadata |
| `erp_vehicles` | Vehicle master |

---

## 3. JobCard / Service / Operation / QC / Delivery

**Table count:** 15

| Table | Typical role |
|-------|----------------|
| `erp_delivery_control_history` | Delivery control change history |
| `erp_delivery_controls` | Delivery control records |
| `erp_jobcard_change_history` | Job card change history |
| `erp_jobcard_part_usage` | Parts used on job cards |
| `erp_jobcard_part_usage_history` | Part usage history |
| `erp_jobcards` | Job card master |
| `erp_operation_cases` | Operation case grouping |
| `erp_operation_delivery_checks` | Pre-delivery checks |
| `erp_operation_history` | Operation state history |
| `erp_operation_qc_decisions` | QC pass/fail decisions |
| `erp_operation_service_steps` | Service step execution |
| `erp_qc_check_history` | QC check history |
| `erp_qc_checks` | QC check definitions/results |
| `erp_service_operation_change_history` | Service operation history |
| `erp_service_operations` | Service operations on job cards |

---

## 4. Inventory / Parts / Supplier / Purchase

**Table count:** 13

| Table | Typical role |
|-------|----------------|
| `erp_inventory_items` | Inventory item catalog |
| `erp_inventory_purchase_history` | Inventory purchase history |
| `erp_inventory_purchase_requests` | Inventory-driven purchase requests |
| `erp_inventory_rule_requests` | Rule-based inventory requests |
| `erp_inventory_stock_movements` | Inventory stock movements |
| `erp_part_reservations` | Reserved parts for jobs |
| `erp_parts` | Parts master |
| `erp_purchase_request_history` | Purchase request history |
| `erp_purchase_requests` | Purchase requests |
| `erp_stock_balances` | Current stock balances |
| `erp_stock_locations` | Warehouse/location master |
| `erp_stock_movements` | Stock movement transactions |
| `erp_suppliers` | Supplier master |

---

## 5. Finance Preview / Payment / Invoice

**Table count:** 11

| Table | Typical role |
|-------|----------------|
| `erp_finance_history` | Finance module change history |
| `erp_finance_labour_rates` | Labour rate reference |
| `erp_finance_part_margin_rules` | Part margin rules |
| `erp_finance_service_price_list` | Service price list |
| `erp_financial_summary_snapshots` | Financial summary snapshots |
| `erp_invoice_previews` | Invoice preview records |
| `erp_jobcard_cost_headers` | Job card cost header |
| `erp_jobcard_cost_lines` | Job card cost line items |
| `erp_payment_history` | Payment change history |
| `erp_payment_records` | Payment record detail |
| `erp_payments` | Payment master |

> **Note:** Finance tables support **preview** and operational costing only. **No official accounting activation.**

---

## 6. CRM / Customer Experience / Upsell

**Table count:** 6

| Table | Typical role |
|-------|----------------|
| `erp_crm_followup_records` | CRM follow-up records |
| `erp_crm_followup_schedules` | Scheduled follow-ups |
| `erp_crm_history` | CRM module history |
| `erp_customer_satisfaction_surveys` | Satisfaction surveys |
| `erp_customer_score_cards` | Customer scoring |
| `erp_upsell_opportunities` | Upsell opportunity tracking |

---

## 7. HR

**Table count:** 7

| Table | Typical role |
|-------|----------------|
| `erp_hr_attendance_records` | Attendance entries |
| `erp_hr_disciplinary_records` | Disciplinary records |
| `erp_hr_employees` | Employee master |
| `erp_hr_employment_contracts` | Employment contracts |
| `erp_hr_history` | HR module history |
| `erp_hr_payroll_previews` | Payroll preview (not official payroll) |
| `erp_hr_training_records` | Training records |

---

## 8. Rule Engine / Workflow Decisions

**Table count:** 4

| Table | Typical role |
|-------|----------------|
| `erp_rule_audit_history` | Rule engine audit trail |
| `erp_rule_decisions` | Rule evaluation decisions |
| `erp_rule_definitions` | Business rule definitions |
| `erp_service_approval_requests` | Service approval workflow requests |

---

## 9. Reporting / KPI / Soft Run / Commercial Preview

**Table count:** 13

| Table | Typical role |
|-------|----------------|
| `erp_business_kpi_snapshots` | KPI snapshot storage |
| `erp_commercial_demo_registry` | Demo registry entries |
| `erp_commercial_package_plans` | Commercial package plans |
| `erp_commercial_readiness_checks` | Readiness check results |
| `erp_commercial_release_history` | Commercial release history |
| `erp_license_preview_models` | License preview models |
| `erp_management_report_history` | Management report history |
| `erp_soft_run_audit_checks` | Soft-run audit checks |
| `erp_soft_run_pilot_feedback` | Pilot user feedback |
| `erp_soft_run_pilot_flow_snapshots` | Pilot flow snapshots |
| `erp_soft_run_pilot_history` | Soft-run pilot history |
| `erp_soft_run_pilot_scenarios` | Pilot scenario definitions |
| `erp_soft_run_pilots` | Soft-run pilot sessions |

> **Note:** Commercial and soft-run tables are **preview/pilot** scope. **No production SaaS activation.**

---

## Domain Summary

| # | Domain | Tables |
|---|--------|--------|
| 1 | Identity / Access / Security | 16 |
| 2 | Customer / Intake / Contract / Vehicle | 11 |
| 3 | JobCard / Service / Operation / QC / Delivery | 15 |
| 4 | Inventory / Parts / Supplier / Purchase | 13 |
| 5 | Finance Preview / Payment / Invoice | 11 |
| 6 | CRM / Customer Experience / Upsell | 6 |
| 7 | HR | 7 |
| 8 | Rule Engine / Workflow Decisions | 4 |
| 9 | Reporting / KPI / Soft Run / Commercial Preview | 13 |
| | **Total** | **96** |

---

## Product Boundary

- Documentation only — no schema change
- **No payment gateway/billing/tax integration created**
- **No public customer portal activation**

---

**END OF DOMAIN TABLE MAP**
