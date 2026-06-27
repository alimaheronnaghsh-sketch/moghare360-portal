# Mission 26 - Permission and Audit Checks

## Permissions Used

| Page / Action | Permission Key |
|---------------|----------------|
| Create POST | purchase.request.create |
| List page | purchase.request.list |
| Detail page | purchase.request.view |

Placeholder owner (user_id 10001) allowed when permission not in DB map.

## Audit on Create
- action_code: PURCHASE_REQUEST_CREATED
- old_status: NULL
- new_status: DRAFT or SUBMITTED
- changed_by_user_id: authenticated user
- change_note: Mission 26 prototype message

## Security Controls on Create
- Auth Context
- Permission Guard
- CSRF token
- ODBC transaction (request + history atomic)

## Read-Only Pages
- Auth Context + Permission Guard only
- No POST handlers
- No write operations

## Prohibited Side Effects
- No silent approval
- No automatic finance write
- No automatic stock write
