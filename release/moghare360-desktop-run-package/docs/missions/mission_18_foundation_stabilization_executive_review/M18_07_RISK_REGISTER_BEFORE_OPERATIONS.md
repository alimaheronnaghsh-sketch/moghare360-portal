# Risk Register Before Operations

## Purpose
This document registers operational risks that must be addressed before Service Operation, Inventory, and Finance phases.

## Risk Register

### R-01 — Service Status Ambiguity
| Field | Value |
|-------|-------|
| Risk | JobCard status model is defined (DRAFT through CANCELLED) but transition engine is not implemented |
| Impact | Unclear ownership of status changes; risk of ad-hoc status updates without audit |
| Current Mitigation | Mission 17 allows only DRAFT/RECEIVED at create; no transitions implemented |
| Required Before Operations | Formal status transition rules, guards, and history per transition |

### R-02 — Inventory Stock Deduction Risk
| Field | Value |
|-------|-------|
| Risk | Parts usage without inventory reservation or stock control could cause negative stock or duplicate deduction |
| Impact | Financial loss, incorrect stock, operational disputes |
| Current Mitigation | No inventory write exists in M05–M17 |
| Required Before Operations | Inventory foundation, reservation model, and controlled deduction with audit |

### R-03 — Finance Write Without Invoice Boundary
| Field | Value |
|-------|-------|
| Risk | Direct finance writes without invoice / payment boundary could bypass approval and audit |
| Impact | Revenue leakage, compliance failure, unreconciled accounts |
| Current Mitigation | No finance write exists in M05–M17 |
| Required Before Operations | Invoice boundary design, payment rules, and finance audit strategy |

### R-04 — QC and Delivery Not Yet Controlled
| Field | Value |
|-------|-------|
| Risk | QC_READY, DELIVERED, and CLOSED statuses exist in design but have no controlled implementation |
| Impact | Premature vehicle release, missing quality checks, incomplete closure |
| Current Mitigation | Status transitions blocked in Mission 17 |
| Required Before Operations | QC checklist design, delivery confirmation, and closure workflow with history |

### R-05 — Purchase Approval Not Designed Yet
| Field | Value |
|-------|-------|
| Risk | Parts procurement without approval rules could authorize unauthorized purchases |
| Impact | Cost overrun, supplier disputes, audit gaps |
| Current Mitigation | approval_rule_count = 16 exists at core level but purchase workflow not linked to JobCard |
| Required Before Operations | Purchase request design, approval chain, and audit per approval step |

### R-06 — Soft Run Not Allowed Before Mission 30
| Field | Value |
|-------|-------|
| Risk | Starting Soft Run operations before full operational stack is ready could expose incomplete workflows to real shop activity |
| Impact | Data integrity failure, user confusion, rollback difficulty |
| Current Mitigation | Mission 18 explicitly marks system as not Soft Run Ready |
| Required Before Operations | Complete operational mission chain through Mission 30 per project controller decision |

## Risk Severity Summary
| ID | Risk | Severity |
|----|------|----------|
| R-01 | Service status ambiguity | High |
| R-02 | Inventory stock deduction | Critical |
| R-03 | Finance without invoice boundary | Critical |
| R-04 | QC and Delivery uncontrolled | High |
| R-05 | Purchase approval not designed | High |
| R-06 | Soft Run before Mission 30 | Critical |

## Mission 18 Boundary
Mission 18 registers risks only.
No risk mitigation code or SQL is implemented.

## Final Risk Decision
Operational expansion into Service Operation, Inventory, and Finance is **blocked** until risks R-01 through R-06 are addressed through approved design and implementation missions.
