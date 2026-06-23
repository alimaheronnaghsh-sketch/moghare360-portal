# Future Permission Guard Helper Plan

## Future Helper File
Future helper file:

includes/erp-permission-guard.php

## Possible Functions
Possible future functions:

- erp_guard_action()
- erp_guard_require()
- erp_guard_can()
- erp_guard_denied()
- erp_guard_context()
- erp_guard_audit_denied_later()

## Helper Responsibilities
The future Permission Guard helper should centralize:

- action key validation
- permission key requirement
- Auth Context integration
- CSRF requirement flag
- workflow state requirement
- deny behavior
- future denied audit hook

## Dependency
The future helper depends on:

- Mission 8 Auth Context helper
- Mission 9 Guard Map
- future CSRF boundary
- future audit boundary

## Mission 9 Boundary
No PHP file should be created in Mission 9.

This is a plan only.
