# Testing Plan

## Steps
1. Open `erp-soft-run-home.php?role=owner`
2. Verify KPI cards and module launch cards
3. Open flow test for jobcard_id=1
4. Verify 9 flow steps with OK/PENDING/EMPTY
5. Open `erp-moghare-ready.php`
6. Verify mission checklist and launch buttons
7. Run PHP syntax check on M37 files
8. Confirm no forbidden files modified

## Pass Criteria
- Shell + design system render
- All links navigate to correct UX pages
- No writes from M37 pages
- Safe empty states when tables missing

## Signoff
Update M37_90 and M37_99 after user confirms.
