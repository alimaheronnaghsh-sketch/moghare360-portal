# PHASE 05 — Domain Ownership Map and SQL Change Plan — Boundary

**Status:** Documentation-only boundary

---

## Boundary Type

Phase 05 assigns **proposed domain ownership** to all 96 tables and documents SQL **change plan candidates** — not executable SQL.

---

## What This Phase Does

- Proposed domain ownership map (business-function based)
- Domain ownership summary with row counts
- Ambiguous table review (38 rows)
- ID type alignment plan (10 dual logical IDs)
- Cross-domain FK review (33 cross / 44 intra)
- SQL change plan candidates (no immediate change for 34 tables)

---

## What This Phase Does NOT Do

- Create SQL migration or seed scripts
- Execute SQL in SSMS
- Modify schema, PHP, frontend, `public_html`
- **Do not create SQL yet**
- **Do not alter ID types yet**

---

## Discovery Source

- **Read-only SSMS discovery source** — user-provided Phase 05 output
- Cursor does not connect to SQL Server
- **No SQL required for Cursor**
- **No database modification**
- **No runtime behavior change**

---

**END OF BOUNDARY**
