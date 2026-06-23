# MOGHARE360 — Security Scaffold (`app/security/`)

## Purpose

Future security helpers (session checks, CSRF, permission guards). Does not replace forbidden auth files (`staff-auth.php`, `access-control.php`).

## Status

- **Not active runtime**
- Scaffold only — no production activation
- **No direct database write**

## Architecture

**UI → Validation Engine → Workflow Engine → Database → Audit Log**
