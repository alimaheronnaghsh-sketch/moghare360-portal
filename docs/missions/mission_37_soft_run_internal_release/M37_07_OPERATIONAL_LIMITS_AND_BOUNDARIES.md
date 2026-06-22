# Operational Limits and Boundaries

## Allowed
- Read-only SELECT for KPI and flow test
- Navigation links to UX and prototype pages

## Forbidden
- DB write from M37 pages
- Auth/login/permission change
- Core schema change
- Legacy portal change
- SaaS / tenant / multi-company
- Production deploy
- Invoice finalization / accounting export
- Customer portal / signature
- Payment/stock/purchase/QC/delivery write from release layer

## Final Boundary Decision
M37 is integration and visibility only.
