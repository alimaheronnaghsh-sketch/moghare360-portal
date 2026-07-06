# MOGHARE360 PR-02B — Governance Decision Lock

**Date (UTC):** 2026-07-06  
**Status:** Owner-approved architecture locked for implementation

## Locked Decisions

### 1. Customer identity
- Mobile verified by OTP only
- No password required
- OTP proves mobile control only — not profile completion

### 2. Customer profile (after OTP)
Required fields for new customers:
- `first_name`, `last_name` → canonical `erp_customers.full_name`
- `national_id` (optional) → `erp_customers.national_id`
- `primary_mobile` → OTP-verified mobile
- `second_phone` → `erp_customers.secondary_mobile`
- `residence_address` → `erp_customers.address` + `city`
- `vehicle_delivery_address` → supplemental (`notes` JSON until schema PR-02B proposal)
- `authorized_receiver_name`, `authorized_receiver_phone` → supplemental (`notes` JSON)

### 3. Multi-vehicle
- One customer may own multiple vehicles via `erp_customer_vehicle_relations`
- Customer selects previous vehicle or adds new vehicle before submit

### 4. Online request linkage
- `erp_customer_online_requests.customer_id` required when customer exists/created
- `erp_customer_online_requests.vehicle_id` required when vehicle resolved/created
- `request_payload_json` is supplemental audit/context only

### 5. OTP source of truth
- Server: `m360_otp_is_verified($mobile)` + optional `otp_verified_token` hash_equals
- `#mobile_verified` hidden field is UI hint only — not security authority

### 6. Submit path
- **No mirror curl loopback** for final submit
- Shared `m360_customer_online_submit()` used by `customer-request.php` and `api/customer/request.php`

### 7. Customer UI
Step-based journey (not tomari scroll):
1. Mobile + OTP
2. Profile
3. Select/Add vehicle
4. Request/symptoms
5. Visit date
6. Contract acknowledgment placeholder
7. Submit + tracking

### 8. Contract
- Online contract required architecturally
- Full legal signing deferred to PR-02C
- No invented legal text in PR-02B

## Labels

| Label | Value |
|-------|-------|
| PR_02B_DECISION_LOCK_CREATED | yes |
| OTP_ONLY_LOGIN_LOCKED | yes |
| CUSTOMER_PROFILE_REQUIRED_AFTER_OTP | yes |
| MULTI_VEHICLE_LOCKED | yes |
| ONLINE_REQUEST_CUSTOMER_VEHICLE_LINK_REQUIRED | yes |
| OTP_SINGLE_SOURCE_LOCKED | yes |
| MIRROR_LOOPBACK_ELIMINATION_REQUIRED | yes |
| CUSTOMER_STEP_BASED_FLOW_LOCKED | yes |
| ONLINE_CONTRACT_ARCHITECTURE_LOCKED | yes |
