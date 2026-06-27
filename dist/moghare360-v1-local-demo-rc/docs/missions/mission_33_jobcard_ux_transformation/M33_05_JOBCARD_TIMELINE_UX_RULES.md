# JobCard Timeline UX Rules

## Page
`erp-jobcard-timeline-ux.php`

## Sources (read-only, safe table check)
| Table | Source label |
|-------|--------------|
| erp_jobcard_change_history | JobCard |
| erp_service_operation_change_history | Service |
| erp_jobcard_part_usage_history | Parts |
| erp_purchase_request_history | Purchase |
| erp_payment_history | Payment |
| erp_qc_check_history | QC |
| erp_delivery_control_history | Delivery |

## Item Fields
- action_code
- status (new_status)
- user_id (changed_by_user_id)
- timestamp (changed_at)
- optional note

## Safety
- INFORMATION_SCHEMA.TABLES check before query
- Missing table = skip silently
- Empty timeline = user message

## Final Timeline Decision
Merged descending timeline with source badges.
