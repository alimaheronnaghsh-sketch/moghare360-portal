# MOGHARE360 — Domain Modules Scaffold (`app/modules/`)

## Purpose

Future domain-specific ERP modules. Each subfolder maps to a core business domain.

## Status

- **Not active runtime**
- Scaffold only — no production activation
- **No direct database write**

## Modules

| Folder | Domain |
|--------|--------|
| `customer/` | Customer intake and profile |
| `vehicle/` | Vehicle registration and binding |
| `contract/` | Service contracts |
| `jobcard/` | Workshop job cards |
| `inventory/` | Parts and stock |
| `crm/` | Follow-up and satisfaction |
| `finance_preview/` | Payment preview (not official accounting) |
| `hr/` | Internal HR |
| `reporting/` | KPI and operational reports |
| `audit/` | Audit log access |

## Architecture

**UI → Validation Engine → Workflow Engine → Database → Audit Log**
