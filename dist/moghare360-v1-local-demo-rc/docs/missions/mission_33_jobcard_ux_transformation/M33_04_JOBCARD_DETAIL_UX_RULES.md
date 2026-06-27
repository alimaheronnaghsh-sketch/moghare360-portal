# JobCard Detail UX Rules

## Page
`erp-jobcard-detail-ux.php`

## Query
- jobcard_id from querystring, default 1
- Guard: jobcard.view

## Sections
1. Status flow visualization
2. JobCard summary grid
3. Customer / vehicle binding cards
4. Service operations list
5. Technician readiness panel (display only)
6. Parts usage summary (if exists)
7. Payment summary (if exists)
8. QC / delivery status (if exists)
9. Action panel — links only to controlled prototypes

## No Write
Action panel does not POST; links to existing M17–M30 pages.

## Final Detail Decision
Single-page operational view with prototype deep links.
