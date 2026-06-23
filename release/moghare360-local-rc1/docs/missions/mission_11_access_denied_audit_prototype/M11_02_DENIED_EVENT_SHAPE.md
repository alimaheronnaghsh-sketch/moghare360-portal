# Denied Event Shape

## Purpose
This document defines the Mission 11 simulation-only denied event shape.

## Required Fields

| Field | Value / Rule |
|---|---|
| actor_user_id | Positive integer user id |
| action_key | Stable action identifier |
| permission_key | Required permission key evaluated |
| target_entity | Entity being acted on |
| target_id | Optional target identifier |
| decision | DENIED |
| reason | Internal safe reason text |
| ip_address | Placeholder or provided value |
| user_agent | Placeholder or provided value |
| created_at | UTC timestamp |
| audit_mode | SIMULATION_ONLY |
| write_performed | false |

## Example Event

```php
[
    'actor_user_id' => 10001,
    'action_key' => 'admin.dashboard.view',
    'permission_key' => 'placeholder_admin_dashboard_view',
    'target_entity' => 'admin_dashboard',
    'target_id' => 'local-readonly-test',
    'decision' => 'DENIED',
    'reason' => 'Missing placeholder permission in Mission 11 simulation',
    'ip_address' => 'placeholder',
    'user_agent' => 'placeholder',
    'created_at' => '2026-06-20T12:00:00Z',
    'audit_mode' => 'SIMULATION_ONLY',
    'write_performed' => false,
]
```

## Validation Rules
`erp_access_denied_validate_event()` confirms:

- all required fields exist
- decision = DENIED
- actor_user_id is positive integer
- audit_mode = SIMULATION_ONLY
- write_performed = false

## Simulation Output
`erp_access_denied_simulate()` returns:

- simulated = true
- write_performed = false
- safe_message
- event

## Mission 11 Boundary
Event shape is prototype metadata only.
No audit INSERT is performed.
