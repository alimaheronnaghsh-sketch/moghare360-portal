# MOGHARE360 V1 — Position Seed Cleanup Backlog (P11.4.3)

## Status

**Deferred to P11.4.3** — requires owner approval before any seed or model changes.

P11.4.2 addressed UX only: department-dependent position dropdowns and server-side validation. **No INSERT/UPDATE/DELETE on `core_positions`.** **No schema ALTER.**

## Current state (post P11.4.2)

- 14 departments in `core_departments`
- 43 positions in `core_positions` (seed from `core_v0_05_seed_org.sql`)
- Duplicate generic labels (مدیر واحد, کارشناس / پرسنل, etc.) across departments
- UX mitigated by filtering positions per department (typically 3–4 choices per unit)

## Proposed standard position model (P11.4.3 — not implemented)

### Generic organizational units

- مدیر واحد
- سرپرست واحد
- کارشناس
- کارمند

### Workshop / hall units

- مدیر سالن
- سرپرست برق
- سرپرست مکانیک
- مکانیک
- برق
- کمک مکانیک
- کمک برق

## P11.4.3 prerequisites

- [ ] Owner sign-off on target taxonomy
- [ ] Migration plan for existing `core_staff_profiles.position_id` references
- [ ] No change to Auth/Login, `core_roles`, `core_permissions`, or `core_role_permissions` without separate approval

## Explicit non-goals for P11.4.2

- No position renames or deactivations
- No new supervisor/employee/hall positions added in this phase
- No `role_code` auto-mapping from position
