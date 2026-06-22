# Assignment UX Boundary

## What UX Shows
- assigned_to_user_id from erp_service_operations (read-only)
- Optional filter on technician workflow page

## What UX Does NOT Do
- No UPDATE assigned_to_user_id
- No INSERT assignment records
- No call to service.operation.assign write path

## Future
Real assignment via controlled prototype when user navigates to M20 pages.

## Final Assignment Boundary
Display placeholder only — assignment write forbidden in M35.
