# PHASE 4 — Purchase Request

Pages:
- `public_html/erp-purchase-request-create.php` — form
- `public_html/submit-purchase-request.php` — controlled write
- `public_html/submit-purchase-status-update.php` — lifecycle updates

## Create

- Required: `requested_part_name`, `requested_qty`
- Auto `request_code`: `PR-YYYYMMDD-HHMMSS-random4`
- Status on create: `REQUESTED`
- Movement: `PURCHASE_REQUEST`
- Does not increase `pending_receive_qty` until status `PENDING_RECEIVE`

## Status Lifecycle

`REQUESTED` → `SUPPLIER_PENDING` → `ORDERED` → `PENDING_RECEIVE` → `RECEIVED` (or `CANCELLED`)

| Transition | Stock effect |
|------------|--------------|
| → PENDING_RECEIVE | +`pending_receive_qty`, movement PENDING_RECEIVE |
| → RECEIVED | +`available_qty`, −`pending_receive_qty`, movement RECEIVE |

## Rule Engine Prefill

`?rule_decision_id=` loads data from `erp_inventory_rule_requests` when available.

CSRF: `inventory_purchase_create`, `inventory_purchase_status`
