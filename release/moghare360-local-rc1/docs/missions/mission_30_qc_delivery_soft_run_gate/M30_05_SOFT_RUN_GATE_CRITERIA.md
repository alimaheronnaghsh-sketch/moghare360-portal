# Mission 30 - Soft Run Gate Criteria

## Gate Result
- **SOFT RUN READY** — all required checks pass
- **SOFT RUN BLOCKED** — with reason string

## Required Checks
| Check | Required |
|-------|----------|
| Customer exists | Yes |
| Vehicle exists | Yes |
| JobCard exists | Yes |
| Service operation exists | Yes |
| Part usage | Optional |
| Payment | Optional / documented |
| QC passed | Yes |
| Delivery allowed (READY or RELEASED) | Yes |
| Audit/history | Yes |

## Display Statements (Read-Only)
- No forbidden files changed
- No final invoice
- No customer portal
- No production deploy

## Page
erp-soft-run-readiness.php — read-only, jobcard_id default 1
