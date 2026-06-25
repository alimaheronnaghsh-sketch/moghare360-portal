# PHASE 6 — Follow-up Scheduler

`crm_create_post_delivery_followup()` sets `scheduled_at` = delivery time + 3 days (from `erp_operation_delivery_checks.checked_at` or case `DELIVERED`), else now + 3 days.

Manual schedules via follow-up board form.
