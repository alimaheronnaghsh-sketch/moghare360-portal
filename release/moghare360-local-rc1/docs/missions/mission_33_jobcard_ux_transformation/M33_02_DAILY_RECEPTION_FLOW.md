# Daily Reception Flow

## Steps (Create UX)
1. Register customer + vehicle (M15 — erp-customer-vehicle-create.php)
2. Create customer-vehicle relation (OWNER, ACTIVE)
3. Open JobCard create (M17 — erp-jobcard-create.php)
4. Select relation_id, set RECEIVED status
5. Continue to service ops, parts, payment via controlled prototypes

## UX Page
`erp-jobcard-create-ux.php` — visual guide + mock disabled form + links

## Final Flow Decision
Reception sees 4-step flow before controlled create.
