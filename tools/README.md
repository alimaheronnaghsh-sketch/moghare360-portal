# MOGHARE360 — Tools (`tools/`)

## Purpose

CLI test tools, packaging scripts, and phase validation utilities for MOGHARE360 ERP.

## Status

- **Tools are test/support only unless explicitly approved** by ChatGPT phase scope
- Existing tools (e.g. `test-phase-*.php`, `package-moghare360-*.ps1`) remain unchanged in Phase 01
- **Not active runtime** — tools do not serve production traffic

## Rules

- No auto deployment scripts that overwrite production config
- No modification of forbidden auth/config files via tools
- Packaging excludes `private/`, secrets, `.bak` files

## Architecture Reference

**UI → Validation Engine → Workflow Engine → Database → Audit Log**

## Product Boundary

- No production SaaS activation
- No public customer portal activation
