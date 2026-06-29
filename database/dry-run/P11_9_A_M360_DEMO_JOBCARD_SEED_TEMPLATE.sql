/*
================================================================================
MOGHARE360 P11.9-A — M360-DEMO JobCard Seed TEMPLATE (Guarded)
File: P11_9_A_M360_DEMO_JOBCARD_SEED_TEMPLATE.sql
================================================================================

WARNING: TEMPLATE ONLY — NOT AUTO-RUN. Operator must review before execution.

Creates (if not exists):
  - Customer M360-DEMO-CUST-001
  - Vehicle M360-DEMO-VEH-001 (Toyota Camry, plate M360-DEMO-001)
  - Customer-vehicle relation
  - JobCard M360-DEMO-001 (status RECEIVED)

Does NOT create:
  - Staff users
  - Permissions / roles
  - Workflow progression beyond initial reception row

Schema basis: mission_15_customer_vehicle_foundation.sql,
              mission_17_jobcard_foundation.sql

ROLLBACK: If executed inside transaction and issue found before COMMIT,
          run ROLLBACK TRANSACTION;

To undo after COMMIT: manual archive/delete only with owner approval —
                      not included in this template.

P11.9-A pack does NOT execute this script.
================================================================================
*/

USE MOGHARE360_ERP;
GO

SET NOCOUNT ON;

/* --- Operator confirmation gate --- */
DECLARE @CONFIRM_CREATE_M360_DEMO NVARCHAR(50) = N'NO';
DECLARE @OPERATOR_USER_ID INT = 0;  /* SET to valid reception user_id e.g. demo.reception */

IF @CONFIRM_CREATE_M360_DEMO <> N'CREATE_M360_DEMO'
BEGIN
    RAISERROR(N'Set @CONFIRM_CREATE_M360_DEMO to CREATE_M360_DEMO after review.', 16, 1);
    RETURN;
END;

IF @OPERATOR_USER_ID IS NULL OR @OPERATOR_USER_ID < 1
BEGIN
    RAISERROR(N'Set @OPERATOR_USER_ID to a valid reception core_users.user_id before running.', 16, 1);
    RETURN;
END;

IF NOT EXISTS (SELECT 1 FROM dbo.core_users WHERE user_id = @OPERATOR_USER_ID)
BEGIN
    RAISERROR(N'@OPERATOR_USER_ID not found in dbo.core_users.', 16, 1);
    RETURN;
END;

/* --- Duplicate prevention --- */
IF EXISTS (SELECT 1 FROM dbo.erp_jobcards WHERE jobcard_number = N'M360-DEMO-001')
BEGIN
    RAISERROR(N'M360-DEMO-001 JobCard already exists — aborting to prevent duplicate.', 16, 1);
    RETURN;
END;

IF OBJECT_ID(N'dbo.erp_customers', N'U') IS NULL
   OR OBJECT_ID(N'dbo.erp_vehicles', N'U') IS NULL
   OR OBJECT_ID(N'dbo.erp_jobcards', N'U') IS NULL
BEGIN
    RAISERROR(N'Required tables missing (erp_customers, erp_vehicles, erp_jobcards).', 16, 1);
    RETURN;
END;

BEGIN TRANSACTION;

BEGIN TRY
    DECLARE @CustomerId INT;
    DECLARE @VehicleId INT;
    DECLARE @RelationId INT = NULL;
    DECLARE @JobcardId INT;

    DECLARE @CustomerCode NVARCHAR(50) = N'M360-DEMO-CUST-001';
    DECLARE @VehicleCode NVARCHAR(50) = N'M360-DEMO-VEH-001';
    DECLARE @JobcardNumber NVARCHAR(60) = N'M360-DEMO-001';

    /* Customer */
    SELECT @CustomerId = customer_id FROM dbo.erp_customers WHERE customer_code = @CustomerCode;
    IF @CustomerId IS NULL
    BEGIN
        INSERT INTO dbo.erp_customers (
            customer_code, customer_type, full_name, primary_mobile,
            lifecycle_state, created_by_user_id
        ) VALUES (
            @CustomerCode, N'PERSON', N'M360 Demo Customer', N'09000000000',
            N'ACTIVE', @OPERATOR_USER_ID
        );
        SET @CustomerId = SCOPE_IDENTITY();
    END;

    /* Vehicle */
    SELECT @VehicleId = vehicle_id FROM dbo.erp_vehicles WHERE vehicle_code = @VehicleCode;
    IF @VehicleId IS NULL
    BEGIN
        INSERT INTO dbo.erp_vehicles (
            vehicle_code, plate_number, brand, model,
            lifecycle_state, created_by_user_id
        ) VALUES (
            @VehicleCode, N'M360-DEMO-001', N'Toyota', N'Camry',
            N'ACTIVE', @OPERATOR_USER_ID
        );
        SET @VehicleId = SCOPE_IDENTITY();
    END;

    /* Relation (optional table) */
    IF OBJECT_ID(N'dbo.erp_customer_vehicle_relations', N'U') IS NOT NULL
    BEGIN
        SELECT @RelationId = relation_id
        FROM dbo.erp_customer_vehicle_relations
        WHERE customer_id = @CustomerId AND vehicle_id = @VehicleId AND lifecycle_state = N'ACTIVE';

        IF @RelationId IS NULL
        BEGIN
            INSERT INTO dbo.erp_customer_vehicle_relations (
                customer_id, vehicle_id, relation_type, is_primary_owner,
                lifecycle_state, created_by_user_id
            ) VALUES (
                @CustomerId, @VehicleId, N'OWNER', 1,
                N'ACTIVE', @OPERATOR_USER_ID
            );
            SET @RelationId = SCOPE_IDENTITY();
        END;
    END;

    /* JobCard — P2 reception entry RECEIVED */
    IF COL_LENGTH(N'dbo.erp_jobcards', N'relation_id') IS NOT NULL AND @RelationId IS NOT NULL
    BEGIN
        INSERT INTO dbo.erp_jobcards (
            jobcard_number, customer_id, vehicle_id, relation_id,
            reception_user_id, jobcard_status, customer_complaint,
            priority_level, lifecycle_state, created_by_user_id
        ) VALUES (
            @JobcardNumber, @CustomerId, @VehicleId, @RelationId,
            @OPERATOR_USER_ID, N'RECEIVED', N'Dry Run controlled service flow test',
            N'NORMAL', N'ACTIVE', @OPERATOR_USER_ID
        );
    END
    ELSE
    BEGIN
        INSERT INTO dbo.erp_jobcards (
            jobcard_number, customer_id, vehicle_id,
            reception_user_id, jobcard_status, customer_complaint,
            priority_level, lifecycle_state, created_by_user_id
        ) VALUES (
            @JobcardNumber, @CustomerId, @VehicleId,
            @OPERATOR_USER_ID, N'RECEIVED', N'Dry Run controlled service flow test',
            N'NORMAL', N'ACTIVE', @OPERATOR_USER_ID
        );
    END;

    SET @JobcardId = SCOPE_IDENTITY();

    /* Optional history row */
    IF OBJECT_ID(N'dbo.erp_jobcard_change_history', N'U') IS NOT NULL
    BEGIN
        INSERT INTO dbo.erp_jobcard_change_history (
            jobcard_id, change_type, previous_status, new_status,
            change_summary, changed_by_user_id
        ) VALUES (
            @JobcardId, N'JOBCARD_CREATE_DEMO_PACK', NULL, N'RECEIVED',
            N'P11.9-A M360-DEMO seed template — operator reviewed', @OPERATOR_USER_ID
        );
    END;

    COMMIT TRANSACTION;

    PRINT N'=== M360-DEMO seed created successfully ===';
    PRINT N'customer_id=' + CAST(@CustomerId AS NVARCHAR(20));
    PRINT N'vehicle_id=' + CAST(@VehicleId AS NVARCHAR(20));
    PRINT N'jobcard_id=' + CAST(@JobcardId AS NVARCHAR(20));
    PRINT N'jobcard_number=' + @JobcardNumber;
    PRINT N'Record these values in P11_9_A_115_STEP_EXECUTION_LOG_TEMPLATE.md header.';
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION;
    DECLARE @Err NVARCHAR(4000) = ERROR_MESSAGE();
    RAISERROR(N'M360-DEMO seed failed: %s', 16, 1, @Err);
END CATCH;
GO
