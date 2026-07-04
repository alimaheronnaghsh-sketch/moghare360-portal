-- MOGHARE360 P11.9-C-2B — ROOT CAUSE DATA EVIDENCE (READ ONLY)
-- Target DB: MOGHARE360_ERP (select manually if name differs)
-- DO NOT INSERT/UPDATE/DELETE/MERGE/DROP/ALTER/TRUNCATE
-- Inspect online_request_id 18 and 20 only

SET NOCOUNT ON;

PRINT '=== 1. CURRENT DATABASE ===';
SELECT DB_NAME() AS current_database_name;

PRINT '=== 2. TABLE EXISTS: erp_customer_online_requests ===';
SELECT CASE WHEN OBJECT_ID(N'dbo.erp_customer_online_requests', N'U') IS NOT NULL THEN 1 ELSE 0 END AS table_exists;

PRINT '=== 3. COLUMNS: erp_customer_online_requests ===';
SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = N'dbo' AND TABLE_NAME = N'erp_customer_online_requests'
ORDER BY ORDINAL_POSITION;

PRINT '=== 4. RAW ROWS online_request_id IN (18, 20) ===';
IF OBJECT_ID(N'dbo.erp_customer_online_requests', N'U') IS NOT NULL
BEGIN
    SELECT *
    FROM dbo.erp_customer_online_requests
    WHERE online_request_id IN (18, 20)
    ORDER BY online_request_id;
END

PRINT '=== 5. JSON VALIDITY + JSON_VALUE EXTRACTION ===';
IF OBJECT_ID(N'dbo.erp_customer_online_requests', N'U') IS NOT NULL
   AND COL_LENGTH(N'dbo.erp_customer_online_requests', N'request_payload_json') IS NOT NULL
BEGIN
    SELECT
        r.online_request_id,
        ISJSON(r.request_payload_json) AS payload_is_json,
        LEN(r.request_payload_json) AS payload_len,
        JSON_VALUE(r.request_payload_json, '$.plate') AS j_plate,
        JSON_VALUE(r.request_payload_json, '$.car_plate') AS j_car_plate,
        JSON_VALUE(r.request_payload_json, '$.vehicle_plate') AS j_vehicle_plate,
        JSON_VALUE(r.request_payload_json, '$.license_plate') AS j_license_plate,
        JSON_VALUE(r.request_payload_json, '$.vin') AS j_vin,
        JSON_VALUE(r.request_payload_json, '$.chassis') AS j_chassis,
        JSON_VALUE(r.request_payload_json, '$.chassis_no') AS j_chassis_no,
        JSON_VALUE(r.request_payload_json, '$.chassis_number') AS j_chassis_number,
        JSON_VALUE(r.request_payload_json, '$.vehicle_vin') AS j_vehicle_vin,
        JSON_VALUE(r.request_payload_json, '$.brand') AS j_brand,
        JSON_VALUE(r.request_payload_json, '$.vehicle_brand') AS j_vehicle_brand,
        JSON_VALUE(r.request_payload_json, '$.model') AS j_model,
        JSON_VALUE(r.request_payload_json, '$.vehicle_model') AS j_vehicle_model,
        JSON_VALUE(r.request_payload_json, '$.mileage') AS j_mileage,
        JSON_VALUE(r.request_payload_json, '$.kilometer') AS j_kilometer,
        JSON_VALUE(r.request_payload_json, '$.km') AS j_km,
        JSON_VALUE(r.request_payload_json, '$.odometer') AS j_odometer,
        JSON_VALUE(r.request_payload_json, '$.intake_mileage') AS j_intake_mileage,
        JSON_VALUE(r.request_payload_json, '$.fuel') AS j_fuel,
        JSON_VALUE(r.request_payload_json, '$.fuel_level') AS j_fuel_level,
        JSON_VALUE(r.request_payload_json, '$.intake_fuel_level') AS j_intake_fuel_level,
        JSON_VALUE(r.request_payload_json, '$.otp_verified') AS j_otp_verified,
        JSON_QUERY(r.request_payload_json, '$.plate_parts') AS j_plate_parts
    FROM dbo.erp_customer_online_requests r
    WHERE r.online_request_id IN (18, 20)
    ORDER BY r.online_request_id;
END
ELSE
BEGIN
    SELECT 'request_payload_json column not available' AS note;
END

PRINT '=== 6. erp_vehicles (if exists) ===';
IF OBJECT_ID(N'dbo.erp_vehicles', N'U') IS NOT NULL
BEGIN
    SELECT COLUMN_NAME, DATA_TYPE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = N'dbo' AND TABLE_NAME = N'erp_vehicles'
    ORDER BY ORDINAL_POSITION;

    IF COL_LENGTH(N'dbo.erp_customer_online_requests', N'vehicle_id') IS NOT NULL
    BEGIN
        SELECT v.*
        FROM dbo.erp_vehicles v
        INNER JOIN dbo.erp_customer_online_requests r ON r.vehicle_id = v.vehicle_id
        WHERE r.online_request_id IN (18, 20);
    END
END

PRINT '=== 7. erp_customer_vehicle_bindings (if exists) ===';
IF OBJECT_ID(N'dbo.erp_customer_vehicle_bindings', N'U') IS NOT NULL
BEGIN
    SELECT COLUMN_NAME, DATA_TYPE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = N'dbo' AND TABLE_NAME = N'erp_customer_vehicle_bindings'
    ORDER BY ORDINAL_POSITION;

    IF COL_LENGTH(N'dbo.erp_customer_online_requests', N'customer_id') IS NOT NULL
       AND COL_LENGTH(N'dbo.erp_customer_online_requests', N'vehicle_id') IS NOT NULL
    BEGIN
        SELECT b.*
        FROM dbo.erp_customer_vehicle_bindings b
        INNER JOIN dbo.erp_customer_online_requests r
            ON (r.customer_id = b.customer_id OR r.vehicle_id = b.vehicle_id)
        WHERE r.online_request_id IN (18, 20);
    END
END

PRINT '=== 8. erp_customer_intakes (if exists) ===';
IF OBJECT_ID(N'dbo.erp_customer_intakes', N'U') IS NOT NULL
BEGIN
    SELECT COLUMN_NAME, DATA_TYPE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = N'dbo' AND TABLE_NAME = N'erp_customer_intakes'
    ORDER BY ORDINAL_POSITION;

    SELECT i.*
    FROM dbo.erp_customer_intakes i
    INNER JOIN dbo.erp_customer_online_requests r ON r.mobile = i.mobile
    WHERE r.online_request_id IN (18, 20);
END

PRINT '=== 9. erp_jobcards (if exists) ===';
IF OBJECT_ID(N'dbo.erp_jobcards', N'U') IS NOT NULL
BEGIN
    SELECT COLUMN_NAME, DATA_TYPE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = N'dbo' AND TABLE_NAME = N'erp_jobcards'
    ORDER BY ORDINAL_POSITION;

    IF COL_LENGTH(N'dbo.erp_jobcards', N'online_request_id') IS NOT NULL
    BEGIN
        SELECT j.*
        FROM dbo.erp_jobcards j
        WHERE j.online_request_id IN (18, 20);
    END
    ELSE IF COL_LENGTH(N'dbo.erp_customer_online_requests', N'converted_jobcard_id') IS NOT NULL
    BEGIN
        SELECT j.*
        FROM dbo.erp_jobcards j
        INNER JOIN dbo.erp_customer_online_requests r ON r.converted_jobcard_id = j.jobcard_id
        WHERE r.online_request_id IN (18, 20);
    END
END

PRINT '=== 10. ROOT_CAUSE_HINT ===';
IF OBJECT_ID(N'dbo.erp_customer_online_requests', N'U') IS NOT NULL
BEGIN
    ;WITH req AS (
        SELECT
            r.online_request_id,
            r.customer_id,
            r.vehicle_id,
            r.vehicle_plate AS col_vehicle_plate,
            r.request_payload_json,
            CASE WHEN COL_LENGTH(N'dbo.erp_customer_online_requests', N'request_payload_json') IS NOT NULL
                      AND ISJSON(r.request_payload_json) = 1 THEN 1 ELSE 0 END AS payload_json_ok,
            NULLIF(LTRIM(RTRIM(JSON_VALUE(r.request_payload_json, '$.vehicle_plate'))), N'') AS j_vehicle_plate,
            NULLIF(LTRIM(RTRIM(JSON_VALUE(r.request_payload_json, '$.vin'))), N'') AS j_vin,
            NULLIF(LTRIM(RTRIM(JSON_VALUE(r.request_payload_json, '$.brand'))), N'') AS j_brand,
            NULLIF(LTRIM(RTRIM(JSON_VALUE(r.request_payload_json, '$.vehicle_brand'))), N'') AS j_vehicle_brand,
            NULLIF(LTRIM(RTRIM(JSON_VALUE(r.request_payload_json, '$.model'))), N'') AS j_model,
            NULLIF(LTRIM(RTRIM(JSON_VALUE(r.request_payload_json, '$.vehicle_model'))), N'') AS j_vehicle_model,
            NULLIF(LTRIM(RTRIM(JSON_VALUE(r.request_payload_json, '$.odometer_km'))), N'') AS j_odometer_km,
            NULLIF(LTRIM(RTRIM(JSON_VALUE(r.request_payload_json, '$.mileage'))), N'') AS j_mileage,
            NULLIF(LTRIM(RTRIM(JSON_VALUE(r.request_payload_json, '$.fuel_level'))), N'') AS j_fuel_level,
            NULLIF(LTRIM(RTRIM(JSON_VALUE(r.request_payload_json, '$.otp_verified'))), N'') AS j_otp_verified
        FROM dbo.erp_customer_online_requests r
        WHERE r.online_request_id IN (18, 20)
    )
    SELECT
        online_request_id,
        CASE WHEN payload_json_ok = 1 AND (
            j_vehicle_plate IS NOT NULL OR j_vin IS NOT NULL OR j_brand IS NOT NULL OR j_vehicle_brand IS NOT NULL
            OR j_model IS NOT NULL OR j_vehicle_model IS NOT NULL OR j_odometer_km IS NOT NULL OR j_mileage IS NOT NULL
            OR j_fuel_level IS NOT NULL
        ) THEN 1 ELSE 0 END AS DATA_PRESENT_IN_PAYLOAD,
        CASE WHEN vehicle_id IS NOT NULL AND vehicle_id > 0 THEN 1 ELSE 0 END AS DATA_PRESENT_IN_STRUCTURED_TABLE,
        CASE WHEN (NULLIF(col_vehicle_plate, N'') IS NULL AND j_vehicle_plate IS NULL AND j_vin IS NULL
            AND j_brand IS NULL AND j_vehicle_brand IS NULL AND j_model IS NULL AND j_vehicle_model IS NULL
            AND j_odometer_km IS NULL AND j_mileage IS NULL AND j_fuel_level IS NULL)
            THEN 1 ELSE 0 END AS DATA_MISSING_IN_REQUEST,
        CASE WHEN vehicle_id IS NULL OR vehicle_id = 0 THEN
            CASE WHEN (j_vehicle_plate IS NOT NULL OR j_vin IS NOT NULL OR j_brand IS NOT NULL OR j_vehicle_brand IS NOT NULL
                OR j_model IS NOT NULL OR j_vehicle_model IS NOT NULL) THEN 1 ELSE 0 END
            ELSE 0 END AS DATA_EXISTS_BUT_NOT_LINKED,
        CASE WHEN COL_LENGTH(N'dbo.erp_customer_online_requests', N'request_payload_json') IS NULL THEN 1 ELSE 0 END AS COLUMN_NOT_AVAILABLE
    FROM req;
END
