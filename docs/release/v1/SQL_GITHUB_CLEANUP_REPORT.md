# MOGHARE360 V1 — SQL Server and GitHub Cleanup Report

**Status:** OWNER_GATE_DESTRUCTIVE_CLEANUP_REQUIRED  
**Branch:** `feature/workshop-service-sales`  
**HEAD at report:** see delivery controller state  
**Database:** `moghare360_ERP` (read-only inventory)

## Inventory summary

| Area | Count / note |
|------|----------------|
| SQL tables/views (INFORMATION_SCHEMA) | 342 |
| SQL procedures | 0 |
| SQL views (sys) | 3 |
| SQL triggers | 3 |
| Foreign keys | 141 |
| Indexes | 894 |
| Git tracked files | ~7798 |
| Nested runtime `public_html` under XAMPP | absent (quarantined earlier) |
| Generated evidence | `tools/_generated/**` (ignored) |

Evidence files (ignored):

- `tools/_generated/v1-delivery/sql-tables.tsv`
- `tools/_generated/v1-delivery/sql-legacy-name-candidates.txt`
- `tools/_generated/v1-delivery/destructive-cleanup-manifest.txt`

## Classification policy

| Class | Meaning | Auto action |
|-------|---------|-------------|
| ACTIVE_CANONICAL | Current workshop/access/invoice/JobCard paths | KEEP |
| ACTIVE_SUPPORTING | Helpers, migrations, CLI tools | KEEP |
| LEGACY_REFERENCED | Old names still linked from active code | KEEP / deprecate later |
| LEGACY_QUARANTINE_CANDIDATE | Name pattern or unused archive | OWNER GATE |
| GENERATED | `_generated`, scans, UAT dumps | ignore / local delete only |
| PRIVATE | configs, credentials, uploads | never commit / never auto-delete |
| DUPLICATE_EXACT | release/dist copies | OWNER GATE before delete |
| TEMPORARY | tmp logs | safe local cleanup only |
| UNKNOWN | unresolved | report only |

## Safe automatic cleanup performed

- None destructive in this phase.
- Nested obsolete runtime tree previously quarantined outside Git.
- Generated evidence remains gitignored.

## Destructive candidates (Owner decision required)

See `tools/_generated/v1-delivery/destructive-cleanup-manifest.txt`.

Recommended default for each SQL name-pattern candidate: **QUARANTINE / KEEP** until dependency proof and row-count sign-off.

Do **not** DROP tables, columns, procedures, or non-identical source trees without explicit Owner approval.

## Rollback

- Git history for any committed file removal.
- SQL: restore from verified `moghare360_ERP` backup before any DROP.
- Runtime quarantine path remains recoverable on disk.

## Next

1. Owner reviews destructive manifest (KEEP / QUARANTINE / DELETE).
2. Delivery controller continues Phase 4 (central UI design system) without destructive SQL/Git actions.
