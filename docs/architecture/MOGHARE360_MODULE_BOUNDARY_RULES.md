# MOGHARE360 — Module Boundary Rules

**Status:** Locked planning rules — Documentation only

---

## Core Write Rules

### 1. No Cross-Module Direct Table Writes

**No module may write directly to another module's owned tables.**

Cross-domain effects must go through the owning module's service API (future `app/modules/{domain}/`).

### 2. Cross-Domain Writes Require Workflow-Approved Service Rules

Cross-domain writes must pass through **workflow-approved service rules** defined in module contracts and cross-domain interaction rules.

Example: JobCard requests part usage → Inventory service validates reservation → Workflow approves → Inventory writes stock.

### 3. UI Cannot Write Directly to Database

**UI cannot write directly to database.** All UI actions POST to module endpoints that invoke Validation Engine and Workflow Engine.

```
UI → Validation Engine → Workflow Engine → Database → Audit Log
```

---

## Engine Mandates

| Engine | Rule |
|--------|------|
| **Validation Engine** | **Mandatory** for all mutating operations |
| **Workflow Engine** | **Mandatory** for state changes |
| **Audit Log** | **Mandatory** for controlled state/action changes |

---

## Activation Boundaries (Not Active)

| Capability | Status |
|------------|--------|
| **Official accounting** | **Not active** |
| **Payment gateway** | **Not active** |
| **Public customer portal** | **Not active** |
| **SaaS production behavior** | **Not active** |

Finance Preview tables are preview scope only. Commercial/soft-run tables are pilot scope only.

---

## Media Boundary

- **Camera direct only**
- **No upload bypass**

Media validation is application-layer; not delegated to raw DB insert from UI.

---

## Identity Boundary

- Production login files (`staff-auth.php`, `staff-login.php`, `access-control.php`) remain **forbidden** to modify without explicit mission
- Identity domain provides read context; does not grant bypass of permission checks

---

## SQL Boundary

- **Do not create SQL yet**
- **Do not alter ID types yet**
- Schema changes require Phase 07+ and ChatGPT-approved packages

---

## Scaffold Boundary

`app/` folders are planning targets. `public_html/` legacy runtime remains unchanged in Phase 06.

---

## Product Boundary

- No payment gateway/billing/tax integration created
- No database schema change in this phase

---

**END OF MODULE BOUNDARY RULES**
