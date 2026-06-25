# Status Progress Visualization

## Board Page
`erp-service-operation-board-ux.php`

## Columns
DRAFT · ASSIGNED · IN_PROGRESS · WAITING_PARTS · DONE · QC_REJECTED · CANCELLED

## Rules
- Group service operations by service_status
- Cards show title, ids, assigned user
- No drag/drop
- No status update API or POST
- Empty column shows safe empty state

## Detail Progress Rail
Horizontal step indicator on detail page using same status order.

## Final Visualization Decision
Display-only kanban for workshop situational awareness.
