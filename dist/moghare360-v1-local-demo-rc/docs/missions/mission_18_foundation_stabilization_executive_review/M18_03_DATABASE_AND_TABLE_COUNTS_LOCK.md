# Database and Table Counts Lock

## Purpose
This document locks database and prototype table counts confirmed through Missions 15 and 17.

## Database
- Database name: moghare360_ERP
- SQL execution: Manual SSMS only for foundation scripts

## Core ERP Counts (Locked)
| Metric | Count |
|--------|-------|
| core_table_count | 16 |
| department_count | 14 |
| position_count | 43 |
| role_count | 18 |
| permission_count | 43 |
| role_permission_count | 162 |
| approval_rule_count | 16 |
| customer_role_count | 0 |
| access_request_count | 2 |

## Mission 15 Foundation Tables
| Table | Confirmed Count (Soft Run) |
|-------|---------------------------|
| dbo.erp_customers | 1 |
| dbo.erp_customer_phones | 1 |
| dbo.erp_vehicles | 1 |
| dbo.erp_customer_vehicle_relations | 1 |
| dbo.erp_customer_vehicle_change_history | 4 |

## Mission 17 Foundation Tables
| Table | Confirmed Count (Soft Run) |
|-------|---------------------------|
| dbo.erp_jobcards | 1 |
| dbo.erp_jobcard_change_history | 2 |

## Test Identity Lock
| Entity | ID |
|--------|-----|
| customer_id | 1 |
| vehicle_id | 1 |
| relation_id | 1 |
| jobcard_id | 1 |

## SQL Safety Lock
- No DROP under Mission 18
- No TRUNCATE under Mission 18
- No destructive migration under Mission 18
- No legacy table modification under Mission 18
- No automatic SQL execution from PHP under Mission 18

## Mission 18 Boundary
Mission 18 does not execute SQL and does not change table counts.
Counts are documented for executive review only.

## Final Count Decision
Database and prototype table counts are locked as documented above.
