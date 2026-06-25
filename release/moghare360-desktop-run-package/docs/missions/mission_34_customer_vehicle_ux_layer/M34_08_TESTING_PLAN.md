# Testing Plan

## Steps
1. Open `erp-customer-vehicle-workbench.php?role=reception`
2. Test GET search: customer, phone, plate, vin
3. Verify KPI cards load
4. Open customer detail UX — verify profile, phones, vehicles, jobcards
5. Open vehicle detail UX — verify profile, owners, jobcards, service ops
6. Open create UX guide — verify flow and M15 link
7. Confirm empty states when tables missing (no fatal error)
8. Run PHP syntax check on all M34 PHP files
9. Confirm no forbidden files modified

## Pass Criteria
- Shell + design system render
- Read-only data displays
- Action links work
- No writes from UX pages

## Signoff
Update M34_90 and M34_99 after user confirms.
