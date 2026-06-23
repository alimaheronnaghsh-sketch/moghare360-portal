# MOGHARE360 — Application Scaffold (`app/`)

## Purpose

Future home for modular ERP application code (backend, frontend, API, engines, domain modules). Separated from legacy `public_html/` runtime until phased migration.

## Status

- **Not active runtime**
- Scaffold only — no production activation
- **No direct database write** from this folder

## Architecture

All future implementation must follow:

**UI → Validation Engine → Workflow Engine → Database → Audit Log**

## Subfolders

| Path | Role |
|------|------|
| `backend/` | Server-side services (future) |
| `frontend/` | UI components (future) |
| `api/` | API layer (future) |
| `security/` | Security helpers (future) |
| `validation/` | Validation Engine (future) |
| `workflow/` | Workflow Engine (future) |
| `modules/` | Domain modules |

## Product Boundary

- No production SaaS activation
- No public customer portal activation
- No official accounting activation
- No payment gateway/billing/tax integration created
