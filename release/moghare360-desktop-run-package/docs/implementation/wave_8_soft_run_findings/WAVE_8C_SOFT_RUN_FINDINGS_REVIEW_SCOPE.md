# WAVE 8C — Soft Run Findings Review Dashboard — Scope

## Status

**WAVE 8C Soft Run Findings Review Dashboard implemented.**

## Objective

Create a controlled read-only review dashboard for Soft Run findings, corrective action monitoring, severity overview, status counts, and history coverage.

## Deliverables

- Review helper: `public_html/includes/moghare360-soft-run-finding-review-helper.php`
- Review dashboard: `public_html/erp-soft-run-finding-review-dashboard.php`
- CLI test: `tools/test-wave-8c-soft-run-finding-review.php`

## Boundaries

- Read-only review/monitoring dashboard only.
- No DB writes from WAVE 8C.
- Does not update finding records.
- Does not perform final vehicle delivery.
- Does not create delivery completion records.
- Does not activate public portal, payment/accounting, or production login.
- Pilot execution create/workflow/review behavior unchanged.
- Finding create/workflow behavior (WAVE 8A/8B) unchanged.

## Roadmap

**Cursor did not decide the next roadmap step.**
