# Mission 24 - Implementation Plan

## Deliverables
1. SQL: erp_jobcard_part_usage + history; optional MISSION_24_TEST_SEED
2. erp-jobcard-part-use.php — usage + ISSUE + history in transaction
3. erp-jobcard-part-readonly-list.php — read-only list
4. tools/test-erp-jobcard-part-usage.php — CLI validation

## Stock Safety
- quantity_on_hand from movement SUM before ISSUE
- Reject insufficient stock
- Post-commit negative check inside transaction before commit

## Success Message
JobCard Part Usage Created OK
