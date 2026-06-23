# Security Boundary Review

## Purpose
This document locks security boundaries confirmed through Missions 08, 10, 15, and 17.

## Required Security Layers (Controlled Writes)
Any future operational write must use:
- Auth Context
- Permission Guard
- CSRF (for browser POST writes)
- Controlled transaction
- Audit / history strategy
- Safe error handling

## Platform Owner Prototype Rule
Local controlled prototypes currently allow:
- user_id = 10001
- username = mahin.paradigm.owner
- roles = owner + system_admin
- Placeholder permission fallback only where real permissions are not yet registered

## Forbidden Changes (Locked)
Mission 18 confirms no authorized change to:
- config.php
- config.example.php
- staff-auth.php
- access-control.php
- Customer Portal files
- Legacy operational files

## Forbidden Operations (Locked)
- No production deploy
- No direct role assignment
- No direct permission change / mutation
- No tenant implementation
- No workflow bypass
- No SQL write without audit / history
- No customer login creation
- No login replacement
- No core_user_roles write
- No access request workflow write

## Secret Exposure Rule
Controlled prototypes must not expose:
- Connection strings
- Password hashes
- Session IDs
- CSRF token values
- Stack traces
- Raw SQL with user data in diagnostics

## Identity Retrieval Rule (Locked from M15/M17)
Forbidden patterns on controlled creates:
- OUTPUT INSERTED
- SCOPE_IDENTITY()
- @@IDENTITY
- IDENT_CURRENT

Approved pattern:
- INSERT + fetch by generated unique business key (customer_code, vehicle_code, jobcard_number)

## Mission 18 Boundary
Mission 18 documents security boundaries only.
No security configuration or code is changed.

## Final Security Decision
Security boundary is locked.
Operational expansion requires explicit new mission approval and must not weaken these rules.
