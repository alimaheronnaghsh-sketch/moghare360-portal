# MOGHARE360 V1 — Post-Run Fix / Development Register

## Purpose

Single control register for all changes after the first real Production Run. Prevents infinite rebuild cycles.

## Register Location

- **UI:** `erp-v1-fix-register.php`
- **SQL:** `erp_v1_post_run_fix_register`
- **Migration:** `public_html/sql/sqlserver/v1_post_run_fix_register.sql`

## Column Schema

| Column | Type | Values |
|--------|------|--------|
| item_id | BIGINT IDENTITY | Auto |
| category | NVARCHAR(30) | BUG, FIX, UI, TRAINING, DATA, SECURITY, V2_BACKLOG |
| severity | NVARCHAR(20) | CRITICAL, HIGH, MEDIUM, LOW |
| source | NVARCHAR(30) | PRODUCTION_RUN, USER_FEEDBACK, OWNER_REVIEW, STAFF_REVIEW |
| description | NVARCHAR(2000) | What was observed |
| affected_module | NVARCHAR(200) | Module / page / API |
| owner_decision | NVARCHAR(500) | Accept / defer / fix now |
| status | NVARCHAR(30) | OPEN, IN_REVIEW, FIXED, DEFERRED_TO_V2, CLOSED |
| created_at | DATETIME2 | Auto |
| closed_at | DATETIME2 | When status = FIXED or CLOSED |

## Category Rules

| Category | Meaning |
|----------|---------|
| BUG | Incorrect behavior in production run |
| FIX | Required correction, not feature |
| UI | Navigation / presentation debt |
| TRAINING | Staff onboarding gap |
| DATA | Seed / import / migration |
| SECURITY | SSL, auth, permission, audit |
| V2_BACKLOG | Explicitly out of V1 — no build until V2 charter |

## Workflow

1. Item discovered during or after production run
2. Record inserted (seed items exist after migration)
3. Owner sets `owner_decision`
4. Status moves: OPEN → IN_REVIEW → FIXED or DEFERRED_TO_V2 → CLOSED
5. **No parallel undocumented work**

## Seed Items (template — not real customer data)

Migration seeds 6 template items covering auth seed, shell nav, SSL, training, V2 backlog, data import.

## Security

- No credentials in register
- No real customer PII in descriptions
- No destructive SQL on register table
