/*
 * MOGHARE360 P8 — Management dashboard / owner control (read-only views, non-destructive)
 */
SET NOCOUNT ON;
GO

IF OBJECT_ID(N'dbo.vw_m360_owner_jobcard_pipeline', N'V') IS NULL
BEGIN
    EXEC(N'
CREATE VIEW dbo.vw_m360_owner_jobcard_pipeline AS
SELECT
    j.jobcard_id,
    j.customer_id,
    j.vehicle_id,
    c.full_name AS customer_name,
    c.primary_mobile AS mobile,
    v.plate_number AS plate_no,
    j.jobcard_status,
    j.technical_status,
    j.estimate_status,
    j.work_execution_status,
    j.qc_status,
    j.delivery_readiness_status,
    j.final_invoice_status,
    j.settlement_status,
    j.customer_delivery_status,
    j.created_at,
    COALESCE(j.updated_at, j.created_at) AS last_activity_at,
    CAST(DATEDIFF(MINUTE, j.created_at, SYSUTCDATETIME()) AS FLOAT) / 60.0 AS age_hours,
    CASE WHEN j.jobcard_status <> N''CLOSED'' AND DATEDIFF(HOUR, COALESCE(j.updated_at, j.created_at), SYSUTCDATETIME()) >= 24 THEN 1 ELSE 0 END AS is_overdue_24,
    CASE WHEN j.jobcard_status <> N''CLOSED'' AND DATEDIFF(HOUR, COALESCE(j.updated_at, j.created_at), SYSUTCDATETIME()) >= 48 THEN 1 ELSE 0 END AS is_overdue_48,
    CASE WHEN j.jobcard_status <> N''CLOSED'' AND DATEDIFF(HOUR, COALESCE(j.updated_at, j.created_at), SYSUTCDATETIME()) >= 72 THEN 1 ELSE 0 END AS is_overdue_72,
    CAST(NULL AS NVARCHAR(50)) AS current_stage,
    CAST(NULL AS NVARCHAR(200)) AS current_stage_label_fa,
    CAST(NULL AS NVARCHAR(500)) AS risk_flags
FROM dbo.erp_jobcards j
LEFT JOIN dbo.erp_customers c ON c.customer_id = j.customer_id
LEFT JOIN dbo.erp_vehicles v ON v.vehicle_id = j.vehicle_id
');
END;
GO

IF OBJECT_ID(N'dbo.vw_m360_owner_financial_control', N'V') IS NULL
BEGIN
    EXEC(N'
CREATE VIEW dbo.vw_m360_owner_financial_control AS
SELECT
    j.jobcard_id,
    fi.final_invoice_id,
    fi.invoice_status,
    COALESCE(fi.total_amount, j.final_invoice_amount, 0) AS final_invoice_amount,
    j.settlement_status,
    COALESCE(sc.total_due_amount, fi.total_amount, 0) AS total_due_amount,
    COALESCE(sc.total_paid_amount, j.settlement_amount_paid, 0) AS total_paid_amount,
    COALESCE(sc.remaining_amount, j.settlement_remaining_amount, 0) AS remaining_amount,
    COALESCE(sc.manager_release_approved, 0) AS manager_release_approved,
    dc.delivery_status,
    CASE
        WHEN (j.qc_status = N''DELIVERY_READY'' OR j.delivery_readiness_status = N''READY'')
             AND COALESCE(sc.remaining_amount, j.settlement_remaining_amount, 0) > 0
             AND j.settlement_status NOT IN (N''SETTLED'', N''MANAGER_RELEASE_APPROVED'')
        THEN 1 ELSE 0
    END AS is_unpaid_delivery_ready,
    CASE
        WHEN (j.customer_delivery_status = N''VEHICLE_RELEASED'' OR j.vehicle_released_at IS NOT NULL)
             AND COALESCE(sc.remaining_amount, j.settlement_remaining_amount, 0) > 0
        THEN 1 ELSE 0
    END AS is_released_with_balance
FROM dbo.erp_jobcards j
LEFT JOIN dbo.erp_final_invoices fi ON fi.final_invoice_id = j.current_final_invoice_id
LEFT JOIN dbo.erp_settlement_controls sc ON sc.jobcard_id = j.jobcard_id
    AND sc.settlement_id = (SELECT TOP 1 settlement_id FROM dbo.erp_settlement_controls sx WHERE sx.jobcard_id = j.jobcard_id ORDER BY settlement_id DESC)
LEFT JOIN dbo.erp_delivery_controls dc ON dc.jobcard_id = j.jobcard_id
');
END;
GO

EXEC(N'
CREATE OR ALTER VIEW dbo.vw_m360_owner_qc_control AS
WITH latest_checklist AS (
    SELECT
        qi.jobcard_id,
        qi.item_key,
        qi.item_result,
        ROW_NUMBER() OVER (
            PARTITION BY qi.jobcard_id, qi.item_key
            ORDER BY ISNULL(qi.checked_at, qx.created_at) DESC, qi.qc_item_id DESC
        ) AS item_rank
    FROM dbo.erp_qc_check_items qi
    INNER JOIN dbo.erp_qc_checks qx ON qx.qc_check_id = qi.qc_check_id
),
checklist_agg AS (
    SELECT
        jobcard_id,
        COUNT(*) AS checklist_total,
        SUM(CASE WHEN item_result = N''PASS'' THEN 1 ELSE 0 END) AS checklist_passed,
        SUM(CASE WHEN item_result = N''FAIL'' THEN 1 ELSE 0 END) AS checklist_failed,
        SUM(CASE WHEN item_result = N''NOT_APPLICABLE'' THEN 1 ELSE 0 END) AS checklist_na,
        SUM(CASE WHEN item_result = N''FAIL'' THEN 1 ELSE 0 END) AS active_fail_count
    FROM latest_checklist
    WHERE item_rank = 1
    GROUP BY jobcard_id
)
SELECT
    j.jobcard_id,
    j.qc_status,
    qc.qc_status AS qc_result,
    j.delivery_readiness_status,
    CASE
        WHEN j.qc_status IN (N''REWORK_REQUIRED'', N''QC_FAILED'')
             OR COALESCE(ca.active_fail_count, 0) > 0
        THEN 1 ELSE 0
    END AS rework_required,
    COALESCE(ca.active_fail_count, 0) AS active_fail_count,
    COALESCE(ca.checklist_total, 0) AS checklist_total,
    COALESCE(ca.checklist_passed, 0) AS checklist_passed,
    COALESCE(ca.checklist_failed, 0) AS checklist_failed,
    COALESCE(ca.checklist_na, 0) AS checklist_na
FROM dbo.erp_jobcards j
LEFT JOIN dbo.erp_qc_checks qc ON qc.jobcard_id = j.jobcard_id
    AND qc.qc_check_id = (
        SELECT TOP 1 qc_check_id FROM dbo.erp_qc_checks qz WHERE qz.jobcard_id = j.jobcard_id ORDER BY qc_check_id DESC
    )
LEFT JOIN checklist_agg ca ON ca.jobcard_id = j.jobcard_id
');
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_erp_jobcards_mgmt_open' AND object_id = OBJECT_ID(N'dbo.erp_jobcards'))
    CREATE INDEX IX_erp_jobcards_mgmt_open ON dbo.erp_jobcards (jobcard_status, updated_at DESC, created_at DESC);
GO
