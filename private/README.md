# MOGHARE360 — Private Config Boundary (`private/`)

## Purpose

**Private config boundary only.** Local runtime credentials and secrets for MOGHARE360 ERP.

## Critical Rules

- **Never commit real secrets** to GitHub
- `erp-config.php` — local server only; excluded from release ZIPs
- `erp-config.example.php` — template only; no real credentials
- Agents must not copy, display, or modify secret values without explicit user authorization

## Status

- **Not active runtime** for scaffold — existing config files unchanged in Phase 01
- **No production activation**

## Forbidden

- Committing `erp-config.php` with real passwords
- Including private config in demo/release packages
- Exposing DB connection strings in documentation or audit pages

## Architecture

Operational writes still require:

**UI → Validation Engine → Workflow Engine → Database → Audit Log**

## Product Boundary

- No cloud storage of secrets
- No business data outside local server
