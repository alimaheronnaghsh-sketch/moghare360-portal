# Mission 24 - JobCard Part Usage Controlled Prototype

## Mission Goal
Implement controlled JobCard part usage with safe ISSUE stock movement per Mission 23 design.

## Dependencies
Mission 20, 22, 23 completed.

## Created Files
- public_html/sql/sqlserver/mission_24_jobcard_part_usage.sql
- public_html/erp-jobcard-part-use.php
- public_html/erp-jobcard-part-readonly-list.php
- tools/test-erp-jobcard-part-usage.php
- docs/missions/mission_24_jobcard_part_usage_controlled/ (8 Markdown files)

## Boundaries
- ISSUE movement only via controlled transaction
- No direct stock balance update
- No negative stock
- No finance / invoice / payment / purchase / delivery write
- No physical delete

## SQL Rule
Manual SSMS execution only.
