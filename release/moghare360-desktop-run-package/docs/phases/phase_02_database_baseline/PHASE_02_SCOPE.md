# PHASE 02 — Database Baseline Documentation — Scope

**Phase:** PHASE 02 — DATABASE BASELINE DOCUMENTATION  
**Status:** Documentation only  
**SQL:** No SQL required

---

## Phase Objective

Create the official database baseline documentation for the current **MOGHARE360_ERP** SQL Server database based on the SSMS table and column inventory provided by the user. This phase is **documentation-only**. Do not create SQL scripts. Do not modify database schema. Do not modify runtime code.

---

## Database Snapshot Source

User-provided SSMS output from **MOGHARE360_ERP** including:

- `dbo` table inventory
- `dbo` column inventory
- **96 detected tables**
- **1224 detected columns**
- Core access/security tables
- ERP operational tables
- Customer / Vehicle / Contract / JobCard / Operation / Inventory / Finance Preview / CRM / HR / Rule / Soft Run / Commercial Preview tables

---

## Allowed Scope

- `docs/phases/phase_02_database_baseline/`
- `docs/database/`

---

## Forbidden Scope

- Production login, auth architecture, permission model
- Private config values
- Database schema modification
- Executable SQL scripts
- PHP backend files (create or modify)
- Frontend runtime files (create or modify)
- `public_html`, release packages, composer, package files, `.env`, `config.php`
- Public customer portal activation
- Production SaaS, official accounting, payment gateway, tax/billing integration
- Commit, push

---

## Product Boundary

- Documentation only
- Database baseline only
- No SQL execution
- No executable SQL script
- No database schema change
- No backend implementation
- No frontend implementation
- No `public_html` change
- No production SaaS activation
- No public customer portal activation
- No official accounting activation
- No payment gateway/billing/tax integration created

---

## Related Documents

- `docs/database/MOGHARE360_DATABASE_BASELINE_SUMMARY.md`
- `docs/database/MOGHARE360_DATABASE_DOMAIN_TABLE_MAP.md`
- `docs/database/MOGHARE360_DATABASE_BASELINE_RISK_AND_GAP_NOTES.md`
- `docs/database/MOGHARE360_DATABASE_BASELINE_DECISION.md`

---

**END OF SCOPE**
