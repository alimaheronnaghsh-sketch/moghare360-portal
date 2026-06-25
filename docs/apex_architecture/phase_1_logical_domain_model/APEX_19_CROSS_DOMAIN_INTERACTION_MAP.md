# APEX 19 — Cross-Domain Interaction Map

## Purpose

Logical map of **how domains interact** in ApexMahinERP. Every interaction is a **service/API boundary** — never direct cross-domain table access.

**Logical only. Not physical schema. No SQL.**

---

## Core Rule

> **No direct cross-domain table access.**
> All interactions below are labeled as **service/API boundary**.

---

## Interaction Overview

```mermaid
flowchart TB
    subgraph org [Organization]
        ORG_SVC[OrganizationService]
    end

    subgraph iam [Identity and Access]
        IAM_SVC[IdentityAccessService]
    end

    subgraph fin [Finance]
        FIN_SVC[FinanceService]
    end

    subgraph proc [Procurement]
        PROC_SVC[ProcurementService]
    end

    subgraph inv [Inventory]
        INV_SVC[InventoryService]
    end

    subgraph crm [CRM and Marketing]
        CRM_SVC[CRMService]
    end

    subgraph hr [HR]
        HR_SVC[HRService]
    end

    subgraph job [Job and Technical Intelligence]
        JOB_SVC[JobTechnicalService]
        TI_SVC[TechnicalIntelligenceService]
    end

    ORG_SVC -->|branch context API| IAM_SVC
    ORG_SVC -->|branch context API| JOB_SVC
    IAM_SVC -->|authorize API| JOB_SVC
    CRM_SVC -->|create intake API| JOB_SVC
    HR_SVC -->|skill profile API| JOB_SVC
    JOB_SVC -->|reserve parts API| INV_SVC
    JOB_SVC -->|prepayment check API| FIN_SVC
    JOB_SVC -->|delivery invoice API| FIN_SVC
    PROC_SVC -->|GRN receive API| INV_SVC
    PROC_SVC -->|payable register API| FIN_SVC
    JOB_SVC -->|case outcome events API| TI_SVC
    JOB_SVC -->|performance events API| HR_SVC
    TI_SVC -->|read case snapshots API| JOB_SVC
```

---

## Interaction Catalog

| # | Source | Target | Interaction | Boundary Type |
|---|--------|--------|-------------|---------------|
| 1 | Job | Inventory | Job requests inventory reservation for JobCard | service/API |
| 2 | Job | Finance | Request prepayment check before work | service/API |
| 3 | Job | Finance | Request delivery invoice / final payment | service/API |
| 4 | Procurement | Inventory | Post GRN receipt to stock | service/API |
| 5 | Procurement | Finance | Register vendor payable from purchase invoice | service/API |
| 6 | CRM | Job | Create appointment → job intake request | service/API |
| 7 | HR | Job | Provide technician skill profile for assignment | service/API |
| 8 | Job | HR | Emit closed-job performance events | service/API event |
| 9 | Job | Technical Intelligence | Provide structured case outcomes | service/API event |
| 10 | Technical Intelligence | Job | Return ranked suggestions (read-only advisory) | service/API query |
| 11 | Identity | All | Authorize user action in domain context | service/API |
| 12 | Organization | All | Resolve branch/tenant scope | service/API |

---

## Example Flow: Job with Prepayment and Parts

```mermaid
sequenceDiagram
    participant CRM as CRM Service
    participant JOB as Job Technical Service
    participant FIN as Finance Service
    participant INV as Inventory Service

    CRM->>JOB: createJobIntake API
    JOB->>FIN: checkPrepayment API
    FIN-->>JOB: prepayment_status
    JOB->>INV: reserveParts API
    INV-->>JOB: reservation_confirmed
    Note over JOB: Work proceeds in Job domain only
    JOB->>INV: issueParts API
    JOB->>FIN: registerDeliveryPayment API
```

No step involves cross-domain persistence access.

---

## Anti-Patterns (Forbidden)

| Anti-Pattern | Correct Pattern |
|--------------|-----------------|
| Job module writes stock quantity | `InventoryService.issueStock(command)` |
| Procurement inserts ledger entry | `FinanceService.registerPayable(command)` |
| CRM updates JobCard status | `JobTechnicalService.createIntake(command)` |
| Technical Intelligence updates JobCard | Advisory only; Job owns state |
| HR stores password hash | Identity & Access owns credentials |

---

## Cursor Statement

**Cursor did not decide the next roadmap step.**
