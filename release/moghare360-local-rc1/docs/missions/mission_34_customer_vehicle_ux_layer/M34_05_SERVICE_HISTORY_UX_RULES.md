# Service History UX Rules

## Customer Detail
- JobCard list: jobcard_number, status, vehicle, reception_at
- Timeline: erp_jobcard_change_history joined to customer's jobcards (TOP 30)

## Vehicle Detail
- JobCard list for vehicle_id
- Service operations via jobcard join

## Display Fields
- action_code / change_type
- new_status
- changed_by_user_id
- changed_at

## Final History Decision
Read-only service history from existing history and jobcard tables.
