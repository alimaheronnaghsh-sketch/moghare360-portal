# MOGHARE360 P1.5 — Intake Contract + Digital Signature Report

## Schema discovery

| Role | Table |
|------|-------|
| Online requests | `dbo.erp_customer_online_requests` (P1) |
| JobCard | `dbo.erp_jobcards` — PK `jobcard_id` |
| JobCard history | `dbo.erp_jobcard_change_history` |
| **New** contracts | `dbo.erp_intake_contracts` |
| **New** signatures | `dbo.erp_intake_contract_signatures` |
| **New** events | `dbo.erp_intake_contract_events` |

- JobCard links to online request via `converted_jobcard_id` (P1 migration)
- No pre-existing `erp_intake_contracts` tables — migration required
- Photos/checklist: bound via snapshot JSON fields (`checklist_summary`) from request/jobcard payload when present

## SQL migration

**File:** `database/migrations/P1_5_intake_contract_signature.sql`  
Non-destructive: CREATE IF NOT EXISTS + ALTER ADD columns on `erp_jobcards`.

## Contract version

**MOGHARE360-INTAKE-V1** stored in:
- `docs/legal/MOGHARE360_INTAKE_CONTRACT_V1.md`
- `public_html/includes/m360-contract-template-render.php`
- DB default `contract_version`

## Files added/modified

### Added
- `database/migrations/P1_5_intake_contract_signature.sql`
- `docs/legal/MOGHARE360_INTAKE_CONTRACT_V1.md`
- `public_html/includes/m360-contract-template-render.php`
- `public_html/includes/m360-intake-contract-helper.php`
- `public_html/includes/m360-contract-signature-helper.php`
- `public_html/erp-intake-contracts.php`
- `public_html/erp-intake-contract-detail.php`
- `public_html/erp-intake-contract-generate.php`
- `public_html/erp-intake-contract-send.php`
- `public_html/customer-intake-contract.php`
- `public_html/customer-intake-contract-sign.php`
- `public_html/api/customer/contract-send-otp.php`
- `public_html/api/customer/contract-sign.php`
- `public_html/assets/js/m360-signature-pad.js`
- `public_html/assets/css/m360-contract.css`
- `tools/test-p1-5-*.php` (4 suites)

### Modified
- `public_html/contract-template-intake.php` — V1 ERP staff preview (no legacy `config.php`)

## Behaviors

### Reception
- List/filter contracts (`erp-intake-contracts.php`)
- Generate from `jobcard_id` (POST + CSRF) — idempotent if active contract exists
- Send secure link via SMS (POST) — token hash only in DB
- Detail preview + event history

### Customer
- Open contract via secure token (`customer-intake-contract.php`)
- Sign on canvas + mandatory checkboxes + OTP (`customer-intake-contract-sign.php`)
- After SIGNED: read-only

### P2 gate
- `m360_contract_can_continue_to_p2($jobcardId)` — true only if contract SIGNED or manager OVERRIDDEN with reason + audit

## Security

- No credentials in repo
- Raw token never stored — SHA-256 hash only
- Token expiration (72h)
- OTP + signature both required
- IP / User-Agent logged
- No Auth/Login core changes
- No production fake OTP
- Reception state changes POST-only + CSRF

## Legal note

Contract text implemented as **MOGHARE360-INTAKE-V1** (articles 1–18 complete). Formal legal review by lawyer is recommended before official production use.

---

MOGHARE360 P1.5 implements the intake contract generation, secure customer review, OTP-based digital signature, and signed-contract gate required before JobCard execution continues into P2.
