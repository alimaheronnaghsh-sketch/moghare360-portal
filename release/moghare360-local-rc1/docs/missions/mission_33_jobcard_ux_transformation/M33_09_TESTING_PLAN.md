# Testing Plan

## Steps
1. Open `erp-jobcard-workbench.php?role=owner`
2. Verify KPI cards and JobCard list load from DB
3. Click Detail UX for a jobcard_id
4. Verify summary, binding, service ops, QC/delivery sections
5. Open timeline UX — verify merged history or empty state
6. Open create UX — verify flow guide and link to erp-jobcard-create.php
7. Test role querystring on workbench
8. Resize browser — responsive layout
9. Run PHP syntax check on all M33 PHP files
10. Confirm no forbidden files modified

## Pass Criteria
- Shell + design system render
- Read-only data displays
- Action links open controlled prototypes
- No PHP errors
- No writes from UX pages

## Signoff
Update M33_90 and M33_99 after user confirms.
