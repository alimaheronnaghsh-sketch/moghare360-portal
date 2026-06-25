# MOGHARE360 — Master 03 API List Plan

**Status:** API-first structure plan — Documentation only  
**SQL:** Not required — no API files created in this phase

---

## Purpose

Define the planned API surface for MOGHARE360 ERP. This is a structural plan only; no PHP endpoints, routes, or controllers are created in the MASTER EXECUTION PACK phase.

---

## API-First Principle

Every operational API must pass the full security and workflow chain before mutating data:

1. Authentication
2. Session validation
3. Role check
4. Permission check
5. Workflow state check
6. Validation Engine (payload)
7. Database write
8. Audit logging

Flow alignment:

**UI → Validation Engine → Workflow Engine → Database → Audit Log**

---

## Planned API Groups

### Auth (read/plan only — no login rewrite)

| Planned endpoint | Method | Purpose |
|------------------|--------|---------|
| `/api/auth/session` | GET | Session status (internal) |
| `/api/auth/logout` | POST | Controlled logout |

*Note: Production login files remain forbidden to modify.*

### Customer

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/customer` | GET | List/search |
| `/api/customer/{id}` | GET | Detail |
| `/api/customer` | POST | Create (validated) |
| `/api/customer/{id}` | PATCH | Update (workflow-gated) |

### Vehicle

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/vehicle` | GET | List |
| `/api/vehicle/{id}` | GET | Detail |
| `/api/vehicle/bind` | POST | Bind to customer |

### Contract

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/contract` | POST | Create contract |
| `/api/contract/{id}` | GET | Detail |
| `/api/contract/{id}/status` | PATCH | Workflow transition |

### JobCard

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/jobcard` | POST | Create |
| `/api/jobcard/{id}` | GET | Detail |
| `/api/jobcard/{id}/status` | PATCH | Service status |

### Workflow

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/workflow/{entity}/{id}/transition` | POST | State transition |
| `/api/workflow/{entity}/{id}/history` | GET | Audit trail |

### Inventory

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/inventory/parts` | GET | Stock view |
| `/api/inventory/reserve` | POST | Part reserve |
| `/api/purchase/request` | POST | Purchase request |

### CRM

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/crm/followup` | POST | Follow-up |
| `/api/crm/satisfaction` | POST | Satisfaction record |

### Finance Preview

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/finance/payment-preview` | POST | Preview payment record |
| `/api/finance/reports` | GET | Preview reports only |

*Not official accounting. No tax invoice APIs.*

### HR

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/hr/employee` | POST | Employee create |
| `/api/hr/contract` | POST | Employment contract |
| `/api/hr/attendance` | POST | Attendance entry |

### Reporting

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/reports/kpi` | GET | KPI read |
| `/api/reports/operation` | GET | Operation performance |
| `/api/reports/crm` | GET | CRM report |

### Audit

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/audit/{module}` | GET | Read-only audit log query |

---

## Per-API Requirements Matrix

| Check | Required on write | Required on read |
|-------|-------------------|------------------|
| Authentication | Yes | Yes |
| Session validation | Yes | Yes |
| Role check | Yes | Yes |
| Permission check | Yes | Yes |
| Workflow state check | Yes (mutations) | Optional |
| Validation Engine | Yes (mutations) | N/A |
| Audit logging | Yes (mutations) | Access log optional |

---

## Product Boundary

- Documentation only
- No API files created
- No backend implementation in this phase
- No production SaaS activation
- No public customer portal activation

---

**END OF API LIST PLAN**
