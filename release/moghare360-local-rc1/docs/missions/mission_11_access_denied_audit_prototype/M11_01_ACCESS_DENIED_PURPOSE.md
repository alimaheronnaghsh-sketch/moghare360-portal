# Access Denied Purpose

## What Access Denied Handling Means
Access Denied handling is the safe runtime response when a user is authenticated but not permitted to perform a requested ERP action.

It must stop the action, return a safe user-facing message, and prepare audit metadata for future recording.

## Why Denied Behavior Must Be Safe
Denied access is a security-sensitive moment.

The system must not leak:
- SQL errors
- stack traces
- permission internals beyond safe policy
- password or secret data

The user should receive a clear, generic denial message only.

## Why Sensitive Errors Must Not Be Exposed
Technical errors can reveal database structure, file paths, or internal permission logic.

Mission 11 locks safe denied messaging and simulation output that avoids sensitive error exposure.

## Why Mission 11 Uses Simulation Only by Default
Mission 11 is a prototype phase.

The project must first define:
- denied event shape
- validation rules
- safe message behavior
- simulation output

Before any real audit INSERT is allowed.

Default mode is Read-Only / Simulation.

## Why Real Audit INSERT Is Deferred
Real denied audit writes require:
- approved audit table design
- future CSRF and permission boundaries
- production audit policy
- separate mission approval

Mission 11 performs no audit INSERT.
`erp_access_denied_should_write_audit()` always returns false.

## Mission 11 Boundary
Simulation only.
No database write.
No login replacement.
