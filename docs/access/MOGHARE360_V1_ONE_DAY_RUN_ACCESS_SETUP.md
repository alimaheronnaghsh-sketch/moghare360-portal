# MOGHARE360 V1 — One-Day Run Access Setup

## Goal

Prepare **dedicated staff logins** for a controlled one-day operational run without changing Auth/Login architecture.

## Minimum checklist

- [ ] Open `erp-access-management.php` — readiness not BLOCKED
- [ ] At least one staff user `user_id >= 20001`
- [ ] RECEPTION + TECHNICIAN (or required units) created with roles assigned
- [ ] `is_login_enabled=1` and `lifecycle_state=ACTIVE` for run users
- [ ] Temporary passwords shared securely (not in Git)
- [ ] Permission preview reviewed per user
- [ ] Owner login reserved for oversight — not daily staff work

## Readiness statuses

| Status | Meaning |
|--------|---------|
| PASS | Staff exist with login enabled |
| WARNING | Staff exist but login disabled, or owner shared-login risk |
| BLOCKED | No staff users — everyone still on owner login |

## Pages

- Console: `erp-access-management.php`
- Create: `erp-access-user-create.php`
- Preview: `erp-access-permission-preview.php`

## Not in scope

- P12 taxonomy
- Payment / accounting / bank / tax
- Permission seed changes
