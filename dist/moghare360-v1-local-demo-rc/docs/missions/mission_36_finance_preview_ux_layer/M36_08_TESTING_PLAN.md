# Testing Plan

## Steps
1. Open `erp-finance-preview-workbench.php?role=finance`
2. Verify KPI and jobcard list
3. Open jobcard finance preview for jobcard_id=1
4. Open payment preview detail for payment_id=1
5. Open invoice preview mock
6. Verify disabled buttons on invoice mock
7. Confirm no POST/write forms
8. Run PHP syntax check
9. Confirm no forbidden files modified

## Pass Criteria
- Shell + design system render
- Read-only financial data displays
- Boundary warnings visible
- No writes from UX pages

## Signoff
Update M36_90 and M36_99 after user confirms.
