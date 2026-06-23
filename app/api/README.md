# MOGHARE360 — API Scaffold (`app/api/`)

## Purpose

Future REST/API endpoints for MOGHARE360 ERP. **API files are not active yet.**

## Status

- **Not active runtime**
- Scaffold only — no production activation
- **No direct database write** without full security chain

## Every Future API Must Pass

1. Authentication
2. Session validation
3. Role check
4. Permission check
5. Workflow state check
6. Validation Engine (payload)
7. Audit logging

## Architecture

**UI → Validation Engine → Workflow Engine → Database → Audit Log**

## Product Boundary

- No production SaaS activation
- No public customer portal activation
