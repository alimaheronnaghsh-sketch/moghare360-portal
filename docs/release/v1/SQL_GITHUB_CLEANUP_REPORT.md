# MOGHARE360 V1 — SQL Server and GitHub Cleanup Report

**Status:** `OWNER_GATE_DESTRUCTIVE_CLEANUP_REQUIRED = RESOLVED_NON_DESTRUCTIVELY`  
**Branch:** `feature/workshop-service-sales`  
**Database:** `moghare360_ERP` (no mutation from this gate)  
**Controller counters:** `DESTRUCTIVE_SQL_ACTIONS=0` · `SQL_TABLES_DROPPED=0` · `SQL_ROWS_DELETED=0` · `SQL_OBJECTS_RENAMED=0` · `ACTIVE_RUNTIME_FILES_DELETED=0`

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
| Nested runtime `public_html` under XAMPP | quarantined outside Git |
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
| LEGACY_QUARANTINE_CANDIDATE | Name pattern or unused archive | OWNER DECISION |
| GENERATED | `_generated`, scans, UAT dumps | ignore / local delete only after proof |
| PRIVATE | configs, credentials, uploads | never commit / never auto-delete |
| DUPLICATE_EXACT | release/dist copies | QUARANTINE (no delete yet) |
| TEMPORARY | tmp logs | DELETE_ALLOWED after proof |
| UNKNOWN | unresolved | report only |

## Owner decisions (destructive-cleanup gate)

| Item | Decision | Notes |
|------|----------|-------|
| All 17 `fin360_*` SQL tables | **KEEP** | Name pattern alone is not obsolescence proof. No DROP / DELETE / TRUNCATE / rename / schema move / constraint disable / guessed compatibility layers / replacement tables. |
| Destructive SQL cleanup | **DEFERRED** | Revisit only after complete E2E UAT, dependency validation, backup/restore rehearsal, Production deployment, operational usage evidence, and final controlled cleanup review. |
| `release/**` / `dist/**` archives | **QUARANTINE** | Keep outside active runtime; exclude from deployment package and navigation; record SHA256 and source/purpose where discoverable; do not commit generated binaries/archives; do not delete yet. |
| Runtime quarantine directory (`C:\xampp\htdocs\_quarantine_moghare360_nested_public_html_20260803`) | **KEEP_QUARANTINED** | Outside active app root; not HTTP-reachable; not in runtime sync or deployment; not committed; do not delete before Production validation and final handover. |
| Generated / temporary artifacts | **DELETE_ALLOWED** | Only after exact path, classification, reference search, SHA256 where relevant, and proof of reproducibility. Must be generated/ignored/temporary/exact duplicate evidence/safely reproducible and unrelated to runtime, audit retention, or private ops records. |
| Non-identical legacy source (PHP/SQL/JS/CSS/config) | **QUARANTINE_OR_KEEP** | Do not automatically delete. |

Labels:

- `FIN360_TABLES_KEEP=yes`
- `NO_SQL_DROP=yes`
- `NO_SQL_DELETE=yes`
- `NO_SQL_RENAME=yes`
- `RELEASE_DIST_QUARANTINED=yes`
- `RUNTIME_QUARANTINE_PRESERVED=yes`
- `GENERATED_TEMP_DELETE_ONLY=yes`
- `DESTRUCTIVE_GATE_RESOLVED=yes`

## Safe automatic cleanup performed

- None destructive in this phase.
- Nested obsolete runtime tree remains quarantined outside Git.
- Generated evidence remains gitignored.
- No SQL DROP / DELETE / TRUNCATE / RENAME executed for this gate.

## Destructive candidates (Owner decisions recorded)

See `tools/_generated/v1-delivery/destructive-cleanup-manifest.txt` (decision column).

Do **not** reopen this gate unless **new** destructive candidates are discovered.

## Rollback

- Git history for any committed file removal.
- SQL: restore from verified `moghare360_ERP` backup before any future DROP (deferred).
- Runtime quarantine path remains recoverable on disk.

## Next

1. ~~Owner reviews destructive manifest~~ — **closed non-destructively**.
2. Delivery controller continues Phase 4 (central UI design system) without destructive SQL/Git actions.
