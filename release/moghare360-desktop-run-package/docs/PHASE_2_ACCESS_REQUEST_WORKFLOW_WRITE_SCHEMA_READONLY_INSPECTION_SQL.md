# Phase 2 Access Request Workflow Write Schema Read-Only Inspection SQL

Project: MOGHARE360 ERP
Phase: Phase 2
Document Type: Schema Read-Only Inspection SQL
Status: Draft for Review
Scope: SQL Text Only - Not Executed

## 1. Purpose

This document defines the controlled read-only SQL inspection script required before any Access Request workflow write implementation.

This document contains SQL text only.

This document does not approve SQL execution.

This document does not approve database writes.

This document does not approve audit/history inserts.

This document does not approve production workflow execution.

## 2. Execution Boundary

Expected database:

```
moghare360_ERP
```

Before future execution, SSMS must be connected to the correct database manually.

The SQL below must be reviewed before execution.

Allowed SQL operation type:

```
SELECT
```

Blocked SQL operation types:

```
INSERT
UPDATE
DELETE
MERGE
DROP
ALTER
TRUNCATE
CREATE
EXEC
GRANT
REVOKE
```

No write operation is approved.

No schema mutation is approved.

No stored procedure execution is approved.

## 3. Read-Only SQL Script

```sql
SELECT
    DB_NAME() AS current_database_name;

SELECT
    s.name AS schema_name,
    t.name AS table_name,
    t.object_id,
    t.create_date,
    t.modify_date
FROM sys.tables AS t
INNER JOIN sys.schemas AS s
    ON s.schema_id = t.schema_id
WHERE
    s.name = 'dbo'
    AND t.name IN (
        'core_access_requests',
        'core_access_request_items',
        'core_access_approvals',
        'core_access_change_history',
        'core_access_suspensions',
        'core_access_restrictions'
    )
ORDER BY
    s.name,
    t.name;

SELECT
    s.name AS schema_name,
    t.name AS table_name,
    c.column_id,
    c.name AS column_name,
    ty.name AS data_type,
    c.max_length,
    c.precision,
    c.scale,
    c.is_nullable,
    c.is_identity,
    c.is_computed,
    dc.name AS default_constraint_name,
    dc.definition AS default_definition
FROM sys.tables AS t
INNER JOIN sys.schemas AS s
    ON s.schema_id = t.schema_id
INNER JOIN sys.columns AS c
    ON c.object_id = t.object_id
INNER JOIN sys.types AS ty
    ON ty.user_type_id = c.user_type_id
LEFT JOIN sys.default_constraints AS dc
    ON dc.parent_object_id = t.object_id
    AND dc.parent_column_id = c.column_id
WHERE
    s.name = 'dbo'
    AND t.name IN (
        'core_access_requests',
        'core_access_request_items',
        'core_access_approvals',
        'core_access_change_history',
        'core_access_suspensions',
        'core_access_restrictions'
    )
ORDER BY
    s.name,
    t.name,
    c.column_id;

SELECT
    s.name AS schema_name,
    t.name AS table_name,
    kc.name AS primary_key_name,
    ic.key_ordinal,
    c.name AS primary_key_column
FROM sys.key_constraints AS kc
INNER JOIN sys.tables AS t
    ON t.object_id = kc.parent_object_id
INNER JOIN sys.schemas AS s
    ON s.schema_id = t.schema_id
INNER JOIN sys.index_columns AS ic
    ON ic.object_id = kc.parent_object_id
    AND ic.index_id = kc.unique_index_id
INNER JOIN sys.columns AS c
    ON c.object_id = ic.object_id
    AND c.column_id = ic.column_id
WHERE
    kc.type = 'PK'
    AND s.name = 'dbo'
    AND t.name IN (
        'core_access_requests',
        'core_access_request_items',
        'core_access_approvals',
        'core_access_change_history',
        'core_access_suspensions',
        'core_access_restrictions'
    )
ORDER BY
    s.name,
    t.name,
    ic.key_ordinal;

SELECT
    fk.name AS foreign_key_name,
    parent_schema.name AS parent_schema_name,
    parent_table.name AS parent_table_name,
    parent_column.name AS parent_column_name,
    referenced_schema.name AS referenced_schema_name,
    referenced_table.name AS referenced_table_name,
    referenced_column.name AS referenced_column_name,
    fk.delete_referential_action_desc,
    fk.update_referential_action_desc
FROM sys.foreign_keys AS fk
INNER JOIN sys.foreign_key_columns AS fkc
    ON fkc.constraint_object_id = fk.object_id
INNER JOIN sys.tables AS parent_table
    ON parent_table.object_id = fkc.parent_object_id
INNER JOIN sys.schemas AS parent_schema
    ON parent_schema.schema_id = parent_table.schema_id
INNER JOIN sys.columns AS parent_column
    ON parent_column.object_id = fkc.parent_object_id
    AND parent_column.column_id = fkc.parent_column_id
INNER JOIN sys.tables AS referenced_table
    ON referenced_table.object_id = fkc.referenced_object_id
INNER JOIN sys.schemas AS referenced_schema
    ON referenced_schema.schema_id = referenced_table.schema_id
INNER JOIN sys.columns AS referenced_column
    ON referenced_column.object_id = fkc.referenced_object_id
    AND referenced_column.column_id = fkc.referenced_column_id
WHERE
    parent_schema.name = 'dbo'
    AND parent_table.name IN (
        'core_access_requests',
        'core_access_request_items',
        'core_access_approvals',
        'core_access_change_history',
        'core_access_suspensions',
        'core_access_restrictions'
    )
ORDER BY
    parent_schema.name,
    parent_table.name,
    fk.name,
    fkc.constraint_column_id;

SELECT
    s.name AS schema_name,
    t.name AS table_name,
    cc.name AS check_constraint_name,
    cc.definition AS check_definition,
    cc.is_disabled,
    cc.is_not_trusted
FROM sys.check_constraints AS cc
INNER JOIN sys.tables AS t
    ON t.object_id = cc.parent_object_id
INNER JOIN sys.schemas AS s
    ON s.schema_id = t.schema_id
WHERE
    s.name = 'dbo'
    AND t.name IN (
        'core_access_requests',
        'core_access_request_items',
        'core_access_approvals',
        'core_access_change_history',
        'core_access_suspensions',
        'core_access_restrictions'
    )
ORDER BY
    s.name,
    t.name,
    cc.name;

SELECT
    s.name AS schema_name,
    t.name AS table_name,
    i.name AS index_name,
    i.type_desc AS index_type,
    i.is_unique,
    i.is_primary_key,
    i.is_unique_constraint,
    ic.key_ordinal,
    ic.is_included_column,
    c.name AS column_name
FROM sys.indexes AS i
INNER JOIN sys.tables AS t
    ON t.object_id = i.object_id
INNER JOIN sys.schemas AS s
    ON s.schema_id = t.schema_id
INNER JOIN sys.index_columns AS ic
    ON ic.object_id = i.object_id
    AND ic.index_id = i.index_id
INNER JOIN sys.columns AS c
    ON c.object_id = ic.object_id
    AND c.column_id = ic.column_id
WHERE
    s.name = 'dbo'
    AND t.name IN (
        'core_access_requests',
        'core_access_request_items',
        'core_access_approvals',
        'core_access_change_history',
        'core_access_suspensions',
        'core_access_restrictions'
    )
ORDER BY
    s.name,
    t.name,
    i.name,
    ic.key_ordinal,
    ic.index_column_id;

SELECT
    s.name AS schema_name,
    t.name AS table_name,
    tr.name AS trigger_name,
    tr.is_disabled,
    tr.is_instead_of_trigger,
    tr.create_date,
    tr.modify_date
FROM sys.triggers AS tr
INNER JOIN sys.tables AS t
    ON t.object_id = tr.parent_id
INNER JOIN sys.schemas AS s
    ON s.schema_id = t.schema_id
WHERE
    s.name = 'dbo'
    AND t.name IN (
        'core_access_requests',
        'core_access_request_items',
        'core_access_approvals',
        'core_access_change_history',
        'core_access_suspensions',
        'core_access_restrictions'
    )
ORDER BY
    s.name,
    t.name,
    tr.name;

SELECT
    s.name AS schema_name,
    t.name AS table_name,
    c.name AS candidate_state_or_status_column,
    ty.name AS data_type,
    c.max_length,
    c.is_nullable
FROM sys.tables AS t
INNER JOIN sys.schemas AS s
    ON s.schema_id = t.schema_id
INNER JOIN sys.columns AS c
    ON c.object_id = t.object_id
INNER JOIN sys.types AS ty
    ON ty.user_type_id = c.user_type_id
WHERE
    s.name = 'dbo'
    AND t.name IN (
        'core_access_requests',
        'core_access_request_items',
        'core_access_approvals',
        'core_access_change_history',
        'core_access_suspensions',
        'core_access_restrictions'
    )
    AND (
        c.name LIKE '%status%'
        OR c.name LIKE '%state%'
        OR c.name LIKE '%workflow%'
        OR c.name LIKE '%submitted%'
        OR c.name LIKE '%approved%'
        OR c.name LIKE '%rejected%'
    )
ORDER BY
    s.name,
    t.name,
    c.column_id;

SELECT
    s.name AS schema_name,
    t.name AS table_name,
    c.name AS candidate_actor_or_user_column,
    ty.name AS data_type,
    c.max_length,
    c.is_nullable
FROM sys.tables AS t
INNER JOIN sys.schemas AS s
    ON s.schema_id = t.schema_id
INNER JOIN sys.columns AS c
    ON c.object_id = t.object_id
INNER JOIN sys.types AS ty
    ON ty.user_type_id = c.user_type_id
WHERE
    s.name = 'dbo'
    AND t.name IN (
        'core_access_requests',
        'core_access_request_items',
        'core_access_approvals',
        'core_access_change_history',
        'core_access_suspensions',
        'core_access_restrictions'
    )
    AND (
        c.name LIKE '%user%'
        OR c.name LIKE '%actor%'
        OR c.name LIKE '%requester%'
        OR c.name LIKE '%creator%'
        OR c.name LIKE '%created_by%'
        OR c.name LIKE '%submitted_by%'
        OR c.name LIKE '%approved_by%'
    )
ORDER BY
    s.name,
    t.name,
    c.column_id;

SELECT
    s.name AS schema_name,
    t.name AS table_name,
    c.name AS candidate_concurrency_column,
    ty.name AS data_type,
    c.max_length,
    c.is_nullable
FROM sys.tables AS t
INNER JOIN sys.schemas AS s
    ON s.schema_id = t.schema_id
INNER JOIN sys.columns AS c
    ON c.object_id = t.object_id
INNER JOIN sys.types AS ty
    ON ty.user_type_id = c.user_type_id
WHERE
    s.name = 'dbo'
    AND t.name IN (
        'core_access_requests',
        'core_access_request_items',
        'core_access_approvals',
        'core_access_change_history',
        'core_access_suspensions',
        'core_access_restrictions'
    )
    AND (
        ty.name IN ('rowversion', 'timestamp')
        OR c.name LIKE '%version%'
        OR c.name LIKE '%updated_at%'
        OR c.name LIKE '%modified_at%'
        OR c.name LIKE '%status_updated_at%'
        OR c.name LIKE '%submitted_at%'
    )
ORDER BY
    s.name,
    t.name,
    c.column_id;

SELECT
    s.name AS schema_name,
    t.name AS possible_audit_or_history_table,
    t.create_date,
    t.modify_date
FROM sys.tables AS t
INNER JOIN sys.schemas AS s
    ON s.schema_id = t.schema_id
WHERE
    s.name = 'dbo'
    AND (
        t.name LIKE '%audit%'
        OR t.name LIKE '%history%'
        OR t.name LIKE '%log%'
        OR t.name LIKE '%change%'
    )
ORDER BY
    s.name,
    t.name;

SELECT TOP (10)
    *
FROM dbo.core_access_requests
ORDER BY
    1;

SELECT TOP (10)
    *
FROM dbo.core_access_request_items
ORDER BY
    1;

SELECT TOP (10)
    *
FROM dbo.core_access_approvals
ORDER BY
    1;

SELECT TOP (10)
    *
FROM dbo.core_access_change_history
ORDER BY
    1;

SELECT TOP (10)
    *
FROM dbo.core_access_suspensions
ORDER BY
    1;

SELECT TOP (10)
    *
FROM dbo.core_access_restrictions
ORDER BY
    1;
```

## 4. Review Notes

The script above is intended for read-only inspection only.

The script must not be executed until explicitly approved in a later step.

The future execution result must be captured in:

```
PHASE_2_ACCESS_REQUEST_WORKFLOW_WRITE_SCHEMA_READONLY_INSPECTION_RESULT.md
```

## 5. Explicit Non-Approval

This document does not approve:

* SQL execution
* Production workflow execution
* Database state update
* Access request row mutation
* Approval creation
* Audit insert
* History insert
* Role assignment changes
* Permission changes
* User changes
* Tenant data changes
* SQL schema changes
* Runtime file changes
* Browser write test
* PHP database connection implementation
* Any write-enabled workflow code

Production workflow implementation remains blocked.

Database writes remain blocked.

Audit/history database writes remain blocked.

## 6. Final Decision

This document provides the read-only SQL inspection script text only.

The project may proceed to review this SQL script.

The project may not proceed to execute this SQL script until separately approved.

The project may not proceed to database write implementation.

The project may not proceed to audit/history insert implementation.

The project may not proceed to production workflow execution.
