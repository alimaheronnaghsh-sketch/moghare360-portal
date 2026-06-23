# MOGHARE360 — Controlled Implementation Backlog

**Database:** MOGHARE360_ERP  
**Status:** Planning baseline — Documentation only  
**Phase:** PHASE 08

---

## Backlog Policy

- **Read-only backlog must execute before write backlog**
- **SQL backlog is planning-only until approved** by ChatGPT
- **Runtime implementation is not part of PHASE 08**
- Flow for all future writes: **UI → Validation Engine → Workflow Engine → Database → Audit Log**

---

## Backlog Categories

| Category | Stage | Description |
|----------|-------|-------------|
| Read-only database visibility | 1 | Schema/health/risk viewers |
| Read-only module dashboards | 1 | Per-domain readiness |
| Validation rule visibility | 1–2 | Rule matrix viewers |
| Workflow state visibility | 1–2 | Transition contract viewers |
| Permission gate visibility | 1 | Gate matrix viewers |
| Audit event visibility | 1–2 | Audit contract preview |
| Soft-run operational preview | 2 | Pilot scope read-only |
| Controlled write candidates | 5 | Draft create/submit (not approved) |
| Future SQL package candidates | 6 | Post-gap-analysis SQL (not approved) |

---

## Backlog Items

### Read-Only Database Visibility

| Backlog ID | Domain | Purpose | Dependency | Readiness | Allowed type | Forbidden type | Validation before impl | Test before signoff |
|------------|--------|---------|------------|-----------|--------------|----------------|------------------------|---------------------|
| BL-RO-001 | All | Database baseline summary viewer | Phase 02 docs | FOUNDATION_REFERENCE | Read-only PHP page | INSERT/UPDATE/DELETE | Phase 02 signoff | `test-phase-08-readonly-db.php` (future) |
| BL-RO-002 | All | Structure health dashboard | Phase 03 docs | FOUNDATION_REFERENCE | Read-only | SQL execution | Phase 03 signoff | Row count display audit |
| BL-RO-003 | All | Gap analysis risk board | Phase 04 docs | FOUNDATION_REFERENCE | Read-only | Schema change UI | Phase 04 signoff | No false production claim |

### Read-Only Module Dashboards

| Backlog ID | Domain | Purpose | Dependency | Readiness | Allowed | Forbidden | Validation | Test |
|------------|--------|---------|------------|-----------|---------|-----------|------------|------|
| BL-RO-010 | Identity | RBAC seed visibility | Phase 05, 07 | FOUNDATION_REFERENCE | Read-only list | Permission edit | Ownership map | Auth guard test |
| BL-RO-011 | Customer | Customer table readiness | Phase 05 | SEED_OR_PROTOTYPE | Read-only | Customer write | Validation matrix | Empty state display |
| BL-RO-012 | Vehicle | Vehicle readiness | Phase 05 | SEED_OR_PROTOTYPE | Read-only | Media upload | Media rules doc | Camera rule banner |
| BL-RO-013 | JobCard | JobCard readiness | Phase 05 | SEED_OR_PROTOTYPE | Read-only | JobCard write | Workflow contract | State display only |
| BL-RO-014 | Inventory | Stock/part readiness | Phase 05 | SEED_OR_PROTOTYPE | Read-only | Stock adjust | Module contract | Isolated table note |
| BL-RO-015 | Finance Preview | Preview-only banner | Phase 05, 07 | PREVIEW_ONLY | Read-only | Accounting activation | Error policy E-10 | Preview label required |

### Validation / Workflow / Permission / Audit Visibility

| Backlog ID | Domain | Purpose | Dependency | Readiness | Allowed | Forbidden | Validation | Test |
|------------|--------|---------|------------|-----------|---------|-----------|------------|------|
| BL-RO-020 | All | Validation rule matrix viewer | Phase 07 | FOUNDATION_REFERENCE | Read-only | Rule edit UI | Validation lock | National ID rule visible |
| BL-RO-021 | All | Workflow transition viewer | Phase 07 | FOUNDATION_REFERENCE | Read-only | State change | Workflow lock | Forbidden transitions shown |
| BL-RO-022 | All | Permission gate viewer | Phase 07 | FOUNDATION_REFERENCE | Read-only | Permission model change | Gate matrix | No new permissions |
| BL-RO-023 | Audit | Audit contract viewer | Phase 07 | FOUNDATION_REFERENCE | Read-only | Audit skip | Audit contract | Field list complete |

### Validation Test Console Planning

| Backlog ID | Domain | Purpose | Dependency | Readiness | Allowed | Forbidden | Validation | Test |
|------------|--------|---------|------------|-----------|---------|-----------|------------|------|
| BL-VT-001 | All | Required field test plan | Phase 07 | PLANNING | CLI test spec | Runtime in Phase 08 | Error policy | Test doc review |
| BL-VT-002 | Customer | National ID / mobile format tests | Phase 07 | PLANNING | Test cases | Live DB write | Validation matrix | Format pass/fail cases |
| BL-VT-003 | Vehicle | VIN/plate/media tests | Phase 07 | PLANNING | Test cases | Upload bypass test | Camera direct only | Media violation cases |

### Workflow Simulation Planning

| Backlog ID | Domain | Purpose | Dependency | Readiness | Allowed | Forbidden | Validation | Test |
|------------|--------|---------|------------|-----------|---------|-----------|------------|------|
| BL-WS-001 | JobCard | Allowed transition simulation | Phase 07 | PLANNING | Simulation spec | Runtime engine | Transition contract | DRAFT→SUBMITTED path |
| BL-WS-002 | JobCard | Forbidden transition simulation | Phase 07 | PLANNING | Simulation spec | DRAFT→APPLIED | Forbidden list | Block verification |

### Audit Preview Planning

| Backlog ID | Domain | Purpose | Dependency | Readiness | Allowed | Forbidden | Validation | Test |
|------------|--------|---------|------------|-----------|---------|-----------|------------|------|
| BL-AP-001 | Audit | Audit event field preview | Phase 07 | PLANNING | Preview spec | Skip audit | Audit contract | All fields documented |

### Soft-Run Operational Preview

| Backlog ID | Domain | Purpose | Dependency | Readiness | Allowed | Forbidden | Validation | Test |
|------------|--------|---------|------------|-----------|---------|-----------|------------|------|
| BL-SR-001 | Reporting | Soft-run pilot status read-only | Phase 05 | SOFT_RUN_READY | Read-only | SaaS activation | Module contract | No billing UI |

### Controlled Write Candidates (Planning Only — Not Approved)

| Backlog ID | Domain | Purpose | Dependency | Readiness | Allowed | Forbidden | Validation | Test |
|------------|--------|---------|------------|-----------|---------|-----------|------------|------|
| BL-CW-001 | Customer | Intake submit | Phase 07, read-only signoff | NOT_APPROVED | Future write | Phase 08 impl | Full validation matrix | E2E after engines |
| BL-CW-002 | JobCard | Draft create | BL-RO-013 signoff | NOT_APPROVED | Future write | Direct DB | Workflow DRAFT | Audit on create |

### Future SQL Package Candidates (Planning Only)

| Backlog ID | Domain | Purpose | Dependency | Readiness | Allowed | Forbidden | Validation | Test |
|------------|--------|---------|------------|-----------|---------|-----------|------------|------|
| BL-SQL-001 | All | Gap closure incremental SQL | Phase 04–05, ChatGPT approval | NOT_APPROVED | SSMS script file | Cursor execution | Controlled roadmap | Post-SQL verification |

---

## Implementation Type Legend

| Allowed implementation type | Meaning |
|----------------------------|---------|
| Read-only PHP page | SELECT / static doc render only |
| Read-only | No mutations |
| Test spec / simulation spec | Documentation or future `tools/test-*` |
| Future write | After read-only layer signoff |

| Forbidden implementation type | Meaning |
|--------------------------------|---------|
| INSERT/UPDATE/DELETE | Any data mutation |
| SQL execution | Cursor or auto SSMS |
| Permission model change | Alter `core_permissions` |
| SaaS/accounting/payment activation | Production boundaries |

---

## Product Boundary

- **Controlled implementation backlog** — planning only
- **Do not implement backlog items yet**

---

**END OF CONTROLLED IMPLEMENTATION BACKLOG**
