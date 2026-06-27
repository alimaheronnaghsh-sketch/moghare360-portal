# JobCard Workbench Design

## Page
`erp-jobcard-workbench.php`

## Components
- M32 shell + M31 components
- KPI cards: Active, Waiting Parts, QC Pending, Ready For Delivery
- JobCard table: ID, number, status, customer, vehicle, dates
- Links: Detail UX, Create UX, M17 readonly list

## Data
- SELECT TOP 50 from erp_jobcards with customer/vehicle joins
- KPI COUNT queries (safe fallbacks)

## Guard
- jobcard.list (placeholder owner 10001)

## Final Workbench Decision
Primary JobCard entry point for Soft Run UX.
