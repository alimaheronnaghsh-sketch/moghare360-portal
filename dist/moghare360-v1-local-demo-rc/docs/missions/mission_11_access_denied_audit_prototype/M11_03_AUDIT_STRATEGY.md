# Audit Strategy

## Purpose
This document defines the future audit strategy for denied permission attempts and the Mission 11 simulation boundary.

## Future Audit Strategy for Denied Attempts
When Permission Guard denies an action in a future production phase, the system should:

- stop the action immediately
- show safe Access Denied message
- build denied event metadata
- optionally record denied attempt in audit storage

Future denied audit fields align with Mission 9 strategy:

- actor_user_id
- permission_key
- action_key
- target_entity
- target_id
- ip_address
- user_agent
- occurred_at

## Future Possible core_audit_logs Integration
A future mission may integrate denied events into an approved audit table such as `core_audit_logs`.

That integration is not part of Mission 11.

Mission 11 only prepares the event shape and simulation helper.

## Current Mission 11 Decision
No real INSERT.

`erp_access_denied_should_write_audit()` always returns false.

`erp_access_denied_mode()` returns SIMULATION_ONLY.

## Read-Only / Simulation Mode
Mission 11 default mode is Read-Only / Simulation.

Tests validate:
- event shape
- safe message
- no audit write
- no sensitive error exposure

## Why Audit Write Needs Future Approval
Real audit INSERT affects:
- database schema or approved audit table usage
- retention policy
- privacy and security review
- production deployment controls

Mission 11 does not perform audit write without future mission approval.

## Mission 11 Boundary
Strategy and prototype only.
No audit table write.
No SQL schema change.
