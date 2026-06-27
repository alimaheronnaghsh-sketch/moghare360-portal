# Integration Boundary

## Uses (read-only)
- M31 CSS: design-tokens, rtl, layout, components
- M32 shell: moghare360-ui-shell.php
- M33 CSS: moghare360-jobcard-ux.css
- Auth: existing erp-auth-context (unchanged)
- Guard: existing placeholder actions (unchanged)

## Links To (unchanged prototypes)
- erp-jobcard-create.php
- erp-service-operation-create.php
- erp-jobcard-part-use.php
- erp-payment-create.php
- erp-qc-check.php
- erp-delivery-control.php
- erp-soft-run-readiness.php
- erp-purchase-request-create.php
- erp-jobcard-payment-summary.php

## Forbidden Changes
- config.php, staff-auth.php, access-control.php
- Existing prototype PHP logic
- SQL schema files
- Legacy portal

## Final Boundary Decision
Additive UX pages only.
