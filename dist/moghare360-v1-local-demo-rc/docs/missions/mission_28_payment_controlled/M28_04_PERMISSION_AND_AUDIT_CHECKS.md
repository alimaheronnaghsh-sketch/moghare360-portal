# Mission 28 - Permission and Audit Checks

## Permissions Used

| Page / Action | Permission Key |
|---------------|----------------|
| Create POST | payment.create |
| List page | payment.list |
| JobCard summary | payment.summary.view |

Placeholder owner (user_id 10001) allowed when permission not in DB map.

## Audit on Create
- action_code: PAYMENT_RECEIVED
- old_status: NULL
- new_status: RECEIVED
- changed_by_user_id: authenticated user

## Security Controls on Create
- Auth Context
- Permission Guard
- CSRF token
- ODBC transaction (payment + history atomic)

## Read-Only Pages
- Auth Context + Permission Guard only
- No POST handlers

## Prohibited Side Effects
- No invoice write
- No accounting export
- No supplier payment
- No tax calculation
- No delivery release
- No direct balance overwrite
