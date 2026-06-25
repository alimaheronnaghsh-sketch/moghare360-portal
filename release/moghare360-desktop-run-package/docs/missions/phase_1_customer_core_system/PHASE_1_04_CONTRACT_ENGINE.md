# PHASE 1 — Contract Engine

## Pages

- Form: `erp-customer-contract-create.php`
- Write: `submit-customer-contract.php`

## Contract Types

| Code | Description |
|------|-------------|
| `PAY_PER_SERVICE` | Pay per service |
| `OPEN_AUTHORIZATION` | Open authorization |
| `LIMITED_AUTHORIZATION` | Limited authorization |
| `CORPORATE_FLEET` | Corporate fleet |

## Authorization Modes

| Code | Description |
|------|-------------|
| `NO_PREAUTH` | No pre-authorization |
| `PREAUTH_LIMITED` | Limited pre-auth |
| `PREAUTH_OPEN` | Open pre-auth |
| `APPROVAL_REQUIRED` | Approval required |

## Contract Code

Auto-generated: `CUS-CON-YYYYMMDD-HHMMSS-random4`

## Initial Status

- `DRAFT` — contract saved as draft
- `ACCEPTED` — contract accepted immediately:
  - `status = ACCEPTED`
  - `accepted_at = SYSUTCDATETIME()`
  - `accepted_by = current user`
  - Row in `dbo.erp_customer_contract_acceptances` with `acceptance_type = INTERNAL_CONTROLLED`

## Security

- CSRF purpose: `customer_core_contract`
- Permission: `customer.core.contract.create`

## History

`action_type = CONTRACT_CREATE` in `erp_customer_core_history`.
