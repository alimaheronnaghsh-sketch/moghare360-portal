# Integration Boundary

## Uses
- M31 design system CSS
- M32 shell include
- M34 moghare360-customer-vehicle-ux.css
- M34 moghare360-customer-vehicle-ux-data.php
- Existing auth context (unchanged)
- Guard customer.vehicle.view (unchanged)

## Links To
- erp-customer-vehicle-create.php (M15)
- erp-customer-vehicle-readonly-list.php (M15)
- erp-jobcard-workbench.php (M33)
- erp-jobcard-create-ux.php (M33)
- erp-jobcard-detail-ux.php (M33)
- Controlled prototype pages (payments, service ops, parts)

## Forbidden Changes
- config.php, staff-auth.php, access-control.php
- Legacy portal
- SQL schema files
- Existing M15/M17 prototype logic

## Final Boundary Decision
Additive UX pages only — zero backend impact.
