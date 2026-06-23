# PHASE 3 — Service Approval Trigger

## Function

`rule_engine_check_service_requires_approval($connection, $isOutOfContract, $requestedAmount, $serviceTitle)`

## Logic

- `is_out_of_contract = false` → ALLOWED / CONTINUE_OPERATION
- `is_out_of_contract = true` → APPROVAL_REQUIRED / REQUEST_INTERNAL_APPROVAL
- Creates `erp_service_approval_requests` with type `OUT_OF_CONTRACT_SERVICE`

## Pages

- Board: `erp-service-approval-request.php`
- Submit: `submit-service-approval-request.php`
- CSRF: `rule_approval_decide`

## Approval Outcomes

- APPROVED — history records continuation permission; safe status update if case in WAITING_APPROVAL
- REJECTED / CANCELLED — request closed, audit logged
