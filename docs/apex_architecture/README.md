# ApexMahinERP — Architecture Documentation Index

## Phase 0 Package

| Document | Purpose |
|----------|---------|
| [APEX_00_ARCHITECTURE_FREEZE_STATEMENT.md](APEX_00_ARCHITECTURE_FREEZE_STATEMENT.md) | Official product and architecture freeze |
| [APEX_01_PRODUCT_SCOPE_MVP_AND_PHASE2.md](APEX_01_PRODUCT_SCOPE_MVP_AND_PHASE2.md) | MVP and Phase 2+ scope lock |
| [APEX_02_DOMAIN_BOUNDARY_RULES.md](APEX_02_DOMAIN_BOUNDARY_RULES.md) | Eight domains and boundary rules |
| [APEX_03_HIGH_LEVEL_ENTITY_MAP.md](APEX_03_HIGH_LEVEL_ENTITY_MAP.md) | Logical entity map (not physical schema) |
| [APEX_04_TECHNICAL_INTELLIGENCE_ENGINE_POSITION.md](APEX_04_TECHNICAL_INTELLIGENCE_ENGINE_POSITION.md) | Technical intelligence engine placement |
| [APEX_05_CLEAN_RESTART_PLAN.md](APEX_05_CLEAN_RESTART_PLAN.md) | Clean restart sequence |
| [APEX_06_DATA_OWNERSHIP_PRELIMINARY_RULES.md](APEX_06_DATA_OWNERSHIP_PRELIMINARY_RULES.md) | Preliminary data ownership |
| [APEX_07_ARCHITECTURE_SIGNOFF.md](APEX_07_ARCHITECTURE_SIGNOFF.md) | Signoff gate — pending user review |
| [APEX_90_PHASE_0_RESULT.md](APEX_90_PHASE_0_RESULT.md) | Phase 0 execution result |
| [APEX_99_PHASE_0_SIGNOFF.md](APEX_99_PHASE_0_SIGNOFF.md) | Phase 0 final signoff |

## CLI Test

```text
C:\xampp\php\php.exe tools/test-apex-phase-0-architecture-freeze.php
```

Expected: `APEX PHASE 0 ARCHITECTURE FREEZE TEST PASSED`

## Hard Rules

- Logical model only in Phase 0 — no physical schema
- No SQL before architecture sign-off
- No runtime application changes in Phase 0
