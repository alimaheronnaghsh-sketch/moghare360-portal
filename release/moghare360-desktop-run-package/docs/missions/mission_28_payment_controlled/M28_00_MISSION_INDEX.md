# Mission 28 - Payment Controlled Prototype

## Mission Goal
Implement controlled customer payment create, list, and JobCard payment summary per Mission 27 design.

## Dependencies
Mission 27, Mission 17 completed.

## Created Files
- public_html/sql/sqlserver/mission_28_payment_foundation.sql
- public_html/erp-payment-create.php
- public_html/erp-payment-readonly-list.php
- public_html/erp-jobcard-payment-summary.php
- tools/test-erp-payment-foundation.php
- docs/missions/mission_28_payment_controlled/ (8 Markdown files)

## Boundaries
- Create payment with status RECEIVED only
- Calculated total_received on summary (no balance write)
- No invoice / accounting export / supplier / tax / delivery / purchase / stock write

## SQL Rule
Manual SSMS execution only.

## Success Message
Payment Created OK
