# MOGHARE360 — Workflow Engine Scaffold (`app/workflow/`)

## Purpose

Future Workflow Engine for MOGHARE360 ERP. Governs state transitions and permissions before database writes.

## Status

- **Not active runtime**
- Scaffold only — no production activation
- **No direct database write** — workflow authorizes transitions only

## Workflow States

```
DRAFT → SUBMITTED → UNDER_REVIEW → APPROVED → APPLIED → CLOSED
```

## Rules

- Permission required per transition
- Audit log required per transition
- **No module without Workflow Engine**

## Architecture

**UI → Validation Engine → Workflow Engine → Database → Audit Log**
