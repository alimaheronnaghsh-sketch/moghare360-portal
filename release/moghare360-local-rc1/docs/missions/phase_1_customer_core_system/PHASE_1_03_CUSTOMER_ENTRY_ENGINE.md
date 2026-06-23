# PHASE 1 — Customer Entry Engine

## Pages

- Form: `erp-customer-entry.php`
- Write: `submit-customer-entry.php`

## Fields

`full_name`, `mobile`, `national_code`, `license_plate`, `intake_channel`, `intake_type`, `source_description`, `notes`

## Security

- POST only on submit
- CSRF purpose: `customer_core_entry`
- Permission: `customer.core.entry.create` (placeholder guard)
- No direct INSERT from form page

## Duplicate Check

Checked against (when tables exist):

1. `dbo.erp_customer_intakes` — mobile, national_code, license_plate
2. `dbo.CustomerPhones_v2` — PhoneNumber
3. `dbo.Customers_v2` — Mobile, NationalCode
4. `dbo.Vehicles` — plate column (auto-detected)

Result:

- `NEW` — no matches
- `POSSIBLE_DUPLICATE` — match found; `duplicate_reason` populated

## History

After successful insert, row written to `dbo.erp_customer_core_history` with `action_type = INTAKE_CREATE`.

## Redirect

`erp-customer-core-dashboard.php?phase1=customer_entry_ok`
