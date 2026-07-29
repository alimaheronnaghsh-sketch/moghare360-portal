/*
 * INV360_002 — Complete Inventory360 cycle on existing MOGHARE360_StockCenter
 * Additive / idempotent / non-destructive. No new database. No ERP table changes.
 */
SET NOCOUNT ON;
SET XACT_ABORT ON;
SET QUOTED_IDENTIFIER ON;
SET ANSI_NULLS ON;
SET ANSI_PADDING ON;
SET ANSI_WARNINGS ON;
SET CONCAT_NULL_YIELDS_NULL ON;
SET ARITHABORT ON;

IF DB_NAME() <> N'MOGHARE360_StockCenter'
    THROW 56001, 'INV360_002 must run on MOGHARE360_StockCenter only.', 1;

BEGIN TRY
    BEGIN TRANSACTION;

    /* ---- Parts extensions ---- */
    IF COL_LENGTH(N'dbo.Parts', N'WorkshopCode') IS NULL
        ALTER TABLE dbo.Parts ADD WorkshopCode NVARCHAR(50) NULL;
    IF COL_LENGTH(N'dbo.Parts', N'ItemNameEn') IS NULL
        ALTER TABLE dbo.Parts ADD ItemNameEn NVARCHAR(200) NULL;
    IF COL_LENGTH(N'dbo.Parts', N'CommonName') IS NULL
        ALTER TABLE dbo.Parts ADD CommonName NVARCHAR(200) NULL;
    IF COL_LENGTH(N'dbo.Parts', N'PartNumber') IS NULL
        ALTER TABLE dbo.Parts ADD PartNumber NVARCHAR(100) NULL;
    IF COL_LENGTH(N'dbo.Parts', N'AlternativeCodes') IS NULL
        ALTER TABLE dbo.Parts ADD AlternativeCodes NVARCHAR(500) NULL;
    IF COL_LENGTH(N'dbo.Parts', N'QrCode') IS NULL
        ALTER TABLE dbo.Parts ADD QrCode NVARCHAR(100) NULL;
    IF COL_LENGTH(N'dbo.Parts', N'SubCategory') IS NULL
        ALTER TABLE dbo.Parts ADD SubCategory NVARCHAR(100) NULL;
    IF COL_LENGTH(N'dbo.Parts', N'FamilyName') IS NULL
        ALTER TABLE dbo.Parts ADD FamilyName NVARCHAR(100) NULL;
    IF COL_LENGTH(N'dbo.Parts', N'ManufacturerName') IS NULL
        ALTER TABLE dbo.Parts ADD ManufacturerName NVARCHAR(100) NULL;
    IF COL_LENGTH(N'dbo.Parts', N'CountryOfOrigin') IS NULL
        ALTER TABLE dbo.Parts ADD CountryOfOrigin NVARCHAR(80) NULL;
    IF COL_LENGTH(N'dbo.Parts', N'ItemType') IS NULL
        ALTER TABLE dbo.Parts ADD ItemType NVARCHAR(40) NULL CONSTRAINT DF_Parts_ItemType DEFAULT (N'spare_part');
    IF COL_LENGTH(N'dbo.Parts', N'TechSpecs') IS NULL
        ALTER TABLE dbo.Parts ADD TechSpecs NVARCHAR(1000) NULL;
    IF COL_LENGTH(N'dbo.Parts', N'DimensionsText') IS NULL
        ALTER TABLE dbo.Parts ADD DimensionsText NVARCHAR(120) NULL;
    IF COL_LENGTH(N'dbo.Parts', N'WeightKg') IS NULL
        ALTER TABLE dbo.Parts ADD WeightKg DECIMAL(18,3) NULL;
    IF COL_LENGTH(N'dbo.Parts', N'ColorName') IS NULL
        ALTER TABLE dbo.Parts ADD ColorName NVARCHAR(60) NULL;
    IF COL_LENGTH(N'dbo.Parts', N'MaterialName') IS NULL
        ALTER TABLE dbo.Parts ADD MaterialName NVARCHAR(80) NULL;
    IF COL_LENGTH(N'dbo.Parts', N'CapacityText') IS NULL
        ALTER TABLE dbo.Parts ADD CapacityText NVARCHAR(80) NULL;
    IF COL_LENGTH(N'dbo.Parts', N'InstallSide') IS NULL
        ALTER TABLE dbo.Parts ADD InstallSide NVARCHAR(20) NULL CONSTRAINT DF_Parts_InstallSide DEFAULT (N'none');
    IF COL_LENGTH(N'dbo.Parts', N'ItemStatus') IS NULL
        ALTER TABLE dbo.Parts ADD ItemStatus NVARCHAR(40) NULL CONSTRAINT DF_Parts_ItemStatus DEFAULT (N'active');
    IF COL_LENGTH(N'dbo.Parts', N'MinStock') IS NULL
        ALTER TABLE dbo.Parts ADD MinStock DECIMAL(18,3) NULL CONSTRAINT DF_Parts_MinStock DEFAULT (0);
    IF COL_LENGTH(N'dbo.Parts', N'MaxStock') IS NULL
        ALTER TABLE dbo.Parts ADD MaxStock DECIMAL(18,3) NULL;
    IF COL_LENGTH(N'dbo.Parts', N'ReorderPoint') IS NULL
        ALTER TABLE dbo.Parts ADD ReorderPoint DECIMAL(18,3) NULL CONSTRAINT DF_Parts_Reorder DEFAULT (0);
    IF COL_LENGTH(N'dbo.Parts', N'SearchNorm') IS NULL
        ALTER TABLE dbo.Parts ADD SearchNorm NVARCHAR(1000) NULL;
    IF COL_LENGTH(N'dbo.Parts', N'StandardCost') IS NULL
        ALTER TABLE dbo.Parts ADD StandardCost DECIMAL(18,4) NULL;
    IF COL_LENGTH(N'dbo.Parts', N'LastPurchasePrice') IS NULL
        ALTER TABLE dbo.Parts ADD LastPurchasePrice DECIMAL(18,4) NULL;

    /* Users: Inventory360 PHP password hash column */
    IF COL_LENGTH(N'dbo.Users', N'AppPasswordHash') IS NULL
        ALTER TABLE dbo.Users ADD AppPasswordHash NVARCHAR(255) NULL;
    IF COL_LENGTH(N'dbo.Users', N'RoleCode') IS NULL
        ALTER TABLE dbo.Users ADD RoleCode NVARCHAR(40) NULL;

    /* Warehouses extensions */
    IF COL_LENGTH(N'dbo.Warehouses', N'WarehouseType') IS NULL
        ALTER TABLE dbo.Warehouses ADD WarehouseType NVARCHAR(40) NULL CONSTRAINT DF_WH_Type DEFAULT (N'main');
    IF COL_LENGTH(N'dbo.Warehouses', N'BranchName') IS NULL
        ALTER TABLE dbo.Warehouses ADD BranchName NVARCHAR(120) NULL;
    IF COL_LENGTH(N'dbo.Warehouses', N'CompanyName') IS NULL
        ALTER TABLE dbo.Warehouses ADD CompanyName NVARCHAR(120) NULL;

    /* Locations hierarchy extensions */
    IF COL_LENGTH(N'dbo.WarehouseLocations', N'AisleCode') IS NULL
        ALTER TABLE dbo.WarehouseLocations ADD AisleCode NVARCHAR(20) NULL;
    IF COL_LENGTH(N'dbo.WarehouseLocations', N'BinCode') IS NULL
        ALTER TABLE dbo.WarehouseLocations ADD BinCode NVARCHAR(20) NULL;
    IF COL_LENGTH(N'dbo.WarehouseLocations', N'LocationName') IS NULL
        ALTER TABLE dbo.WarehouseLocations ADD LocationName NVARCHAR(120) NULL;

    /* Indexes for search */
    IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_Parts_WorkshopCode' AND object_id = OBJECT_ID(N'dbo.Parts'))
        CREATE INDEX IX_Parts_WorkshopCode ON dbo.Parts(WorkshopCode);
    IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_Parts_TechnicalCode' AND object_id = OBJECT_ID(N'dbo.Parts'))
        CREATE INDEX IX_Parts_TechnicalCode ON dbo.Parts(TechnicalCode);
    IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_Parts_ItemName' AND object_id = OBJECT_ID(N'dbo.Parts'))
        CREATE INDEX IX_Parts_ItemName ON dbo.Parts(ItemName);
    IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_Parts_ItemNameEn' AND object_id = OBJECT_ID(N'dbo.Parts'))
        CREATE INDEX IX_Parts_ItemNameEn ON dbo.Parts(ItemNameEn);
    IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_Parts_PartNumber' AND object_id = OBJECT_ID(N'dbo.Parts'))
        CREATE INDEX IX_Parts_PartNumber ON dbo.Parts(PartNumber);
    IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_Parts_OEMCode' AND object_id = OBJECT_ID(N'dbo.Parts'))
        CREATE INDEX IX_Parts_OEMCode ON dbo.Parts(OEMCode);
    IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_Parts_Barcode' AND object_id = OBJECT_ID(N'dbo.Parts'))
        CREATE INDEX IX_Parts_Barcode ON dbo.Parts(Barcode);
    IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_Parts_InternalCode' AND object_id = OBJECT_ID(N'dbo.Parts'))
        CREATE INDEX IX_Parts_InternalCode ON dbo.Parts(InternalCode);
    IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_Parts_AlternativeCodes' AND object_id = OBJECT_ID(N'dbo.Parts'))
        CREATE INDEX IX_Parts_AlternativeCodes ON dbo.Parts(AlternativeCodes);
    /* SearchNorm is NVARCHAR(1000); nonclustered index key limit is 1700 bytes — skip dedicated index; LIKE uses other indexed codes. */

    /* Stock balances */
    IF OBJECT_ID(N'dbo.Inv360StockBalances', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.Inv360StockBalances (
            BalanceID BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            PartID BIGINT NOT NULL,
            WarehouseID INT NULL,
            LocationID BIGINT NULL,
            PhysicalQty DECIMAL(18,3) NOT NULL CONSTRAINT DF_Inv360Bal_Phys DEFAULT (0),
            ReservedQty DECIMAL(18,3) NOT NULL CONSTRAINT DF_Inv360Bal_Res DEFAULT (0),
            QuarantineQty DECIMAL(18,3) NOT NULL CONSTRAINT DF_Inv360Bal_Qua DEFAULT (0),
            InTransitQty DECIMAL(18,3) NOT NULL CONSTRAINT DF_Inv360Bal_Trn DEFAULT (0),
            BlockedQty DECIMAL(18,3) NOT NULL CONSTRAINT DF_Inv360Bal_Blk DEFAULT (0),
            ConsignmentQty DECIMAL(18,3) NOT NULL CONSTRAINT DF_Inv360Bal_Con DEFAULT (0),
            UnitCost DECIMAL(18,4) NULL,
            UpdatedAt DATETIME2 NOT NULL CONSTRAINT DF_Inv360Bal_Upd DEFAULT (SYSUTCDATETIME()),
            CONSTRAINT UQ_Inv360Bal UNIQUE (PartID, WarehouseID, LocationID)
        );
    END;

    /* Stock documents */
    IF OBJECT_ID(N'dbo.Inv360StockDocuments', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.Inv360StockDocuments (
            DocumentID BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            DocNo NVARCHAR(40) NOT NULL,
            DocType NVARCHAR(40) NOT NULL,
            DocStatus NVARCHAR(40) NOT NULL CONSTRAINT DF_Inv360Doc_Status DEFAULT (N'draft'),
            DocDate DATETIME2 NOT NULL CONSTRAINT DF_Inv360Doc_Date DEFAULT (SYSUTCDATETIME()),
            SourceWarehouseID INT NULL,
            SourceLocationID BIGINT NULL,
            TargetWarehouseID INT NULL,
            TargetLocationID BIGINT NULL,
            ReasonText NVARCHAR(500) NULL,
            ReferenceType NVARCHAR(40) NULL,
            ReferenceNo NVARCHAR(80) NULL,
            CostCenter NVARCHAR(80) NULL,
            Notes NVARCHAR(1000) NULL,
            CreatedByUserID INT NULL,
            ApprovedByUserID INT NULL,
            PostedByUserID INT NULL,
            PostedAt DATETIME2 NULL,
            CancelledAt DATETIME2 NULL,
            CancelReason NVARCHAR(500) NULL,
            CreatedAt DATETIME2 NOT NULL CONSTRAINT DF_Inv360Doc_Created DEFAULT (SYSUTCDATETIME()),
            UpdatedAt DATETIME2 NULL,
            CONSTRAINT UQ_Inv360DocNo UNIQUE (DocNo)
        );
    END;

    IF OBJECT_ID(N'dbo.Inv360StockDocumentLines', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.Inv360StockDocumentLines (
            LineID BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            DocumentID BIGINT NOT NULL,
            PartID BIGINT NOT NULL,
            Qty DECIMAL(18,3) NOT NULL,
            UnitCost DECIMAL(18,4) NULL,
            LineNote NVARCHAR(300) NULL,
            CONSTRAINT FK_Inv360DocLine_Doc FOREIGN KEY (DocumentID) REFERENCES dbo.Inv360StockDocuments(DocumentID)
        );
    END;

    IF OBJECT_ID(N'dbo.Inv360Reservations', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.Inv360Reservations (
            ReservationID BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            PartID BIGINT NOT NULL,
            WarehouseID INT NULL,
            LocationID BIGINT NULL,
            Qty DECIMAL(18,3) NOT NULL,
            PurposeCode NVARCHAR(40) NOT NULL CONSTRAINT DF_Inv360Res_Purpose DEFAULT (N'manual'),
            PurposeRef NVARCHAR(80) NULL,
            ResStatus NVARCHAR(30) NOT NULL CONSTRAINT DF_Inv360Res_Status DEFAULT (N'active'),
            CreatedByUserID INT NULL,
            CreatedAt DATETIME2 NOT NULL CONSTRAINT DF_Inv360Res_Created DEFAULT (SYSUTCDATETIME()),
            ReleasedAt DATETIME2 NULL,
            Notes NVARCHAR(500) NULL
        );
    END;

    IF OBJECT_ID(N'dbo.Inv360Suppliers', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.Inv360Suppliers (
            SupplierID INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            SupplierCode NVARCHAR(40) NOT NULL,
            SupplierName NVARCHAR(200) NOT NULL,
            LegalName NVARCHAR(200) NULL,
            TaxId NVARCHAR(50) NULL,
            BankAccountNote NVARCHAR(200) NULL,
            ContactName NVARCHAR(120) NULL,
            ContactPhone NVARCHAR(40) NULL,
            ContactEmail NVARCHAR(120) NULL,
            CategoriesText NVARCHAR(300) NULL,
            ContractsNote NVARCHAR(500) NULL,
            LicensesNote NVARCHAR(500) NULL,
            PaymentTerms NVARCHAR(200) NULL,
            CreditLimit DECIMAL(18,2) NULL,
            DelayCount INT NOT NULL CONSTRAINT DF_Inv360Sup_Delay DEFAULT (0),
            MismatchPercent DECIMAL(9,2) NOT NULL CONSTRAINT DF_Inv360Sup_Mismatch DEFAULT (0),
            ReturnPercent DECIMAL(9,2) NOT NULL CONSTRAINT DF_Inv360Sup_Return DEFAULT (0),
            QualityScore DECIMAL(9,2) NOT NULL CONSTRAINT DF_Inv360Sup_Quality DEFAULT (0),
            CoopScore DECIMAL(9,2) NOT NULL CONSTRAINT DF_Inv360Sup_Coop DEFAULT (0),
            SupplierStatus NVARCHAR(30) NOT NULL CONSTRAINT DF_Inv360Sup_Status DEFAULT (N'active'),
            RelatedPartyFlag BIT NOT NULL CONSTRAINT DF_Inv360Sup_Related DEFAULT (0),
            CreatedAt DATETIME2 NOT NULL CONSTRAINT DF_Inv360Sup_Created DEFAULT (SYSUTCDATETIME()),
            CreatedByUserID INT NULL,
            IsActive BIT NOT NULL CONSTRAINT DF_Inv360Sup_Active DEFAULT (1),
            CONSTRAINT UQ_Inv360SupCode UNIQUE (SupplierCode)
        );
    END;

    IF OBJECT_ID(N'dbo.Inv360PurchaseRequests', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.Inv360PurchaseRequests (
            PurchaseRequestID INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            PRNo NVARCHAR(40) NOT NULL,
            RequesterName NVARCHAR(120) NULL,
            DepartmentName NVARCHAR(120) NULL,
            NeededDate DATE NULL,
            UrgencyCode NVARCHAR(20) NOT NULL CONSTRAINT DF_Inv360PR_Urgency DEFAULT (N'normal'),
            ReasonText NVARCHAR(500) NULL,
            PartID BIGINT NULL,
            ItemText NVARCHAR(200) NULL,
            Qty DECIMAL(18,3) NOT NULL,
            CurrentStockSnapshot DECIMAL(18,3) NULL,
            SuggestedSuppliers NVARCHAR(300) NULL,
            AttachmentNote NVARCHAR(500) NULL,
            PRStatus NVARCHAR(40) NOT NULL CONSTRAINT DF_Inv360PR_Status DEFAULT (N'draft'),
            CreatedByUserID INT NULL,
            CreatedAt DATETIME2 NOT NULL CONSTRAINT DF_Inv360PR_Created DEFAULT (SYSUTCDATETIME()),
            CONSTRAINT UQ_Inv360PRNo UNIQUE (PRNo)
        );
    END;

    IF OBJECT_ID(N'dbo.Inv360Rfqs', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.Inv360Rfqs (
            RfqID INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            RfqNo NVARCHAR(40) NOT NULL,
            PurchaseRequestID INT NULL,
            SupplierID INT NULL,
            PartID BIGINT NULL,
            ItemText NVARCHAR(200) NULL,
            UnitPrice DECIMAL(18,4) NOT NULL,
            DiscountPct DECIMAL(9,2) NOT NULL CONSTRAINT DF_Inv360Rfq_Disc DEFAULT (0),
            TaxPct DECIMAL(9,2) NOT NULL CONSTRAINT DF_Inv360Rfq_Tax DEFAULT (0),
            FreightAmount DECIMAL(18,2) NOT NULL CONSTRAINT DF_Inv360Rfq_Freight DEFAULT (0),
            DeliveryDays INT NULL,
            PaymentTerms NVARCHAR(200) NULL,
            QualityGrade NVARCHAR(40) NULL,
            WarrantyText NVARCHAR(200) NULL,
            CurrencyCode NVARCHAR(10) NOT NULL CONSTRAINT DF_Inv360Rfq_Cur DEFAULT (N'IRR'),
            ValidUntil DATE NULL,
            PriceScore DECIMAL(9,2) NULL,
            QualityScore DECIMAL(9,2) NULL,
            OnTimeScore DECIMAL(9,2) NULL,
            PaymentScore DECIMAL(9,2) NULL,
            ResponseScore DECIMAL(9,2) NULL,
            ReturnScore DECIMAL(9,2) NULL,
            TotalScore DECIMAL(9,2) NULL,
            RfqStatus NVARCHAR(30) NOT NULL CONSTRAINT DF_Inv360Rfq_Status DEFAULT (N'received'),
            CreatedByUserID INT NULL,
            CreatedAt DATETIME2 NOT NULL CONSTRAINT DF_Inv360Rfq_Created DEFAULT (SYSUTCDATETIME()),
            CONSTRAINT UQ_Inv360RfqNo UNIQUE (RfqNo)
        );
    END;

    IF OBJECT_ID(N'dbo.Inv360PurchaseOrders', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.Inv360PurchaseOrders (
            PurchaseOrderID INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            PONo NVARCHAR(40) NOT NULL,
            SupplierID INT NULL,
            PurchaseRequestID INT NULL,
            RfqID INT NULL,
            CurrencyCode NVARCHAR(10) NOT NULL CONSTRAINT DF_Inv360PO_Cur DEFAULT (N'IRR'),
            ExchangeRate DECIMAL(18,6) NOT NULL CONSTRAINT DF_Inv360PO_FX DEFAULT (1),
            DeliveryPlace NVARCHAR(200) NULL,
            DeliveryDays INT NULL,
            PaymentTerms NVARCHAR(200) NULL,
            WarrantyText NVARCHAR(200) NULL,
            DelayPenalty NVARCHAR(200) NULL,
            ResponsibleName NVARCHAR(120) NULL,
            POStatus NVARCHAR(40) NOT NULL CONSTRAINT DF_Inv360PO_Status DEFAULT (N'draft'),
            CreatedByUserID INT NULL,
            CreatedAt DATETIME2 NOT NULL CONSTRAINT DF_Inv360PO_Created DEFAULT (SYSUTCDATETIME()),
            CONSTRAINT UQ_Inv360PONo UNIQUE (PONo)
        );
    END;

    IF OBJECT_ID(N'dbo.Inv360PurchaseOrderLines', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.Inv360PurchaseOrderLines (
            POLineID INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            PurchaseOrderID INT NOT NULL,
            PartID BIGINT NULL,
            ItemText NVARCHAR(200) NOT NULL,
            OrderedQty DECIMAL(18,3) NOT NULL,
            ReceivedQty DECIMAL(18,3) NOT NULL CONSTRAINT DF_Inv360POL_Recv DEFAULT (0),
            UnitPrice DECIMAL(18,4) NOT NULL,
            DiscountPct DECIMAL(9,2) NOT NULL CONSTRAINT DF_Inv360POL_Disc DEFAULT (0),
            TaxPct DECIMAL(9,2) NOT NULL CONSTRAINT DF_Inv360POL_Tax DEFAULT (0),
            CONSTRAINT FK_Inv360POL_PO FOREIGN KEY (PurchaseOrderID) REFERENCES dbo.Inv360PurchaseOrders(PurchaseOrderID)
        );
    END;

    IF OBJECT_ID(N'dbo.Inv360GoodsReceipts', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.Inv360GoodsReceipts (
            GoodsReceiptID INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            GRNo NVARCHAR(40) NOT NULL,
            PurchaseOrderID INT NULL,
            WarehouseID INT NULL,
            LocationID BIGINT NULL,
            GRStatus NVARCHAR(40) NOT NULL CONSTRAINT DF_Inv360GR_Status DEFAULT (N'draft'),
            QtyControlNote NVARCHAR(500) NULL,
            QualityControlNote NVARCHAR(500) NULL,
            DocumentControlNote NVARCHAR(500) NULL,
            QCResult NVARCHAR(30) NULL,
            CreatedByUserID INT NULL,
            PostedAt DATETIME2 NULL,
            CreatedAt DATETIME2 NOT NULL CONSTRAINT DF_Inv360GR_Created DEFAULT (SYSUTCDATETIME()),
            CONSTRAINT UQ_Inv360GRNo UNIQUE (GRNo)
        );
    END;

    IF OBJECT_ID(N'dbo.Inv360GoodsReceiptLines', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.Inv360GoodsReceiptLines (
            GRLineID INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            GoodsReceiptID INT NOT NULL,
            POLineID INT NULL,
            PartID BIGINT NULL,
            ItemText NVARCHAR(200) NOT NULL,
            ReceivedQty DECIMAL(18,3) NOT NULL,
            AcceptedQty DECIMAL(18,3) NOT NULL CONSTRAINT DF_Inv360GRL_Acc DEFAULT (0),
            RejectedQty DECIMAL(18,3) NOT NULL CONSTRAINT DF_Inv360GRL_Rej DEFAULT (0),
            QuarantineQty DECIMAL(18,3) NOT NULL CONSTRAINT DF_Inv360GRL_Qua DEFAULT (0),
            UnitCost DECIMAL(18,4) NULL,
            CONSTRAINT FK_Inv360GRL_GR FOREIGN KEY (GoodsReceiptID) REFERENCES dbo.Inv360GoodsReceipts(GoodsReceiptID)
        );
    END;

    IF OBJECT_ID(N'dbo.Inv360QcEvents', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.Inv360QcEvents (
            QcEventID INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            GoodsReceiptID INT NULL,
            PartID BIGINT NULL,
            ActionCode NVARCHAR(40) NOT NULL,
            Qty DECIMAL(18,3) NOT NULL,
            NoteText NVARCHAR(500) NULL,
            CreatedByUserID INT NULL,
            CreatedAt DATETIME2 NOT NULL CONSTRAINT DF_Inv360Qc_Created DEFAULT (SYSUTCDATETIME())
        );
    END;

    IF OBJECT_ID(N'dbo.Inv360SupplierReturns', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.Inv360SupplierReturns (
            SupplierReturnID INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            ReturnNo NVARCHAR(40) NOT NULL,
            SupplierID INT NULL,
            PartID BIGINT NULL,
            Qty DECIMAL(18,3) NOT NULL,
            ReasonText NVARCHAR(500) NOT NULL,
            ReturnStatus NVARCHAR(30) NOT NULL CONSTRAINT DF_Inv360SR_Status DEFAULT (N'draft'),
            CreatedByUserID INT NULL,
            CreatedAt DATETIME2 NOT NULL CONSTRAINT DF_Inv360SR_Created DEFAULT (SYSUTCDATETIME()),
            CONSTRAINT UQ_Inv360SRNo UNIQUE (ReturnNo)
        );
    END;

    IF OBJECT_ID(N'dbo.Inv360CustomerReturns', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.Inv360CustomerReturns (
            CustomerReturnID INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            ReturnNo NVARCHAR(40) NOT NULL,
            CustomerName NVARCHAR(200) NULL,
            PartID BIGINT NULL,
            Qty DECIMAL(18,3) NOT NULL,
            ReasonText NVARCHAR(500) NULL,
            ReturnStatus NVARCHAR(30) NOT NULL CONSTRAINT DF_Inv360CR_Status DEFAULT (N'draft'),
            CreatedByUserID INT NULL,
            CreatedAt DATETIME2 NOT NULL CONSTRAINT DF_Inv360CR_Created DEFAULT (SYSUTCDATETIME()),
            CONSTRAINT UQ_Inv360CRNo UNIQUE (ReturnNo)
        );
    END;

    IF OBJECT_ID(N'dbo.Inv360LandedCosts', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.Inv360LandedCosts (
            LandedCostID INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            RefNo NVARCHAR(40) NOT NULL,
            PartID BIGINT NULL,
            ValuationMethod NVARCHAR(40) NOT NULL CONSTRAINT DF_Inv360LC_Method DEFAULT (N'weighted_average'),
            PurchasePrice DECIMAL(18,4) NOT NULL CONSTRAINT DF_Inv360LC_PP DEFAULT (0),
            ForeignFreight DECIMAL(18,4) NOT NULL CONSTRAINT DF_Inv360LC_FF DEFAULT (0),
            InsuranceAmount DECIMAL(18,4) NOT NULL CONSTRAINT DF_Inv360LC_Ins DEFAULT (0),
            BankFee DECIMAL(18,4) NOT NULL CONSTRAINT DF_Inv360LC_Bank DEFAULT (0),
            InspectionFee DECIMAL(18,4) NOT NULL CONSTRAINT DF_Inv360LC_Insp DEFAULT (0),
            CustomsFee DECIMAL(18,4) NOT NULL CONSTRAINT DF_Inv360LC_Cus DEFAULT (0),
            DutiesAmount DECIMAL(18,4) NOT NULL CONSTRAINT DF_Inv360LC_Dut DEFAULT (0),
            WarehousingFee DECIMAL(18,4) NOT NULL CONSTRAINT DF_Inv360LC_Wh DEFAULT (0),
            ClearanceFee DECIMAL(18,4) NOT NULL CONSTRAINT DF_Inv360LC_Clr DEFAULT (0),
            InlandFreight DECIMAL(18,4) NOT NULL CONSTRAINT DF_Inv360LC_IF DEFAULT (0),
            BrokerFee DECIMAL(18,4) NOT NULL CONSTRAINT DF_Inv360LC_Brk DEFAULT (0),
            OtherDirectCost DECIMAL(18,4) NOT NULL CONSTRAINT DF_Inv360LC_Oth DEFAULT (0),
            AllocationMethod NVARCHAR(40) NOT NULL CONSTRAINT DF_Inv360LC_Alloc DEFAULT (N'by_value'),
            Qty DECIMAL(18,3) NOT NULL CONSTRAINT DF_Inv360LC_Qty DEFAULT (1),
            LandedUnitCost DECIMAL(18,4) NULL,
            CreatedByUserID INT NULL,
            CreatedAt DATETIME2 NOT NULL CONSTRAINT DF_Inv360LC_Created DEFAULT (SYSUTCDATETIME()),
            CONSTRAINT UQ_Inv360LCRef UNIQUE (RefNo)
        );
    END;

    IF OBJECT_ID(N'dbo.Inv360LogisticsRequests', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.Inv360LogisticsRequests (
            LogisticsID INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            RequestCode NVARCHAR(40) NOT NULL,
            CarrierName NVARCHAR(120) NULL,
            VehiclePlate NVARCHAR(40) NULL,
            DriverName NVARCHAR(120) NULL,
            WaybillNo NVARCHAR(80) NULL,
            RouteText NVARCHAR(200) NULL,
            OriginText NVARCHAR(200) NULL,
            DestinationText NVARCHAR(200) NULL,
            WeightKg DECIMAL(18,3) NULL,
            VolumeM3 DECIMAL(18,3) NULL,
            PackageCount INT NULL,
            FreightCost DECIMAL(18,2) NULL,
            PlannedAt DATETIME2 NULL,
            LoadedAt DATETIME2 NULL,
            DispatchedAt DATETIME2 NULL,
            DeliveredAt DATETIME2 NULL,
            ReceiverConfirm NVARCHAR(200) NULL,
            DamageNote NVARCHAR(500) NULL,
            TrackingCode NVARCHAR(80) NULL,
            ProofNote NVARCHAR(500) NULL,
            LogisticsStatus NVARCHAR(40) NOT NULL CONSTRAINT DF_Inv360Log_Status DEFAULT (N'planned'),
            CreatedByUserID INT NULL,
            CreatedAt DATETIME2 NOT NULL CONSTRAINT DF_Inv360Log_Created DEFAULT (SYSUTCDATETIME()),
            CONSTRAINT UQ_Inv360LogCode UNIQUE (RequestCode)
        );
    END;

    IF OBJECT_ID(N'dbo.Inv360ToolsAssets', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.Inv360ToolsAssets (
            ToolAssetID INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            ToolCode NVARCHAR(40) NOT NULL,
            ToolName NVARCHAR(200) NOT NULL,
            SerialNumber NVARCHAR(80) NULL,
            LocationText NVARCHAR(120) NULL,
            AssignedUserName NVARCHAR(120) NULL,
            HealthStatus NVARCHAR(40) NOT NULL CONSTRAINT DF_Inv360Tool_Health DEFAULT (N'good'),
            ServiceDueDate DATE NULL,
            CalibrationDueDate DATE NULL,
            WarrantyText NVARCHAR(200) NULL,
            ChecklistNote NVARCHAR(500) NULL,
            IsActive BIT NOT NULL CONSTRAINT DF_Inv360Tool_Active DEFAULT (1),
            CreatedByUserID INT NULL,
            CreatedAt DATETIME2 NOT NULL CONSTRAINT DF_Inv360Tool_Created DEFAULT (SYSUTCDATETIME()),
            CONSTRAINT UQ_Inv360ToolCode UNIQUE (ToolCode)
        );
    END;

    IF OBJECT_ID(N'dbo.Inv360ToolEvents', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.Inv360ToolEvents (
            ToolEventID INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            ToolAssetID INT NOT NULL,
            EventType NVARCHAR(30) NOT NULL,
            EventUserName NVARCHAR(120) NULL,
            EventAt DATETIME2 NOT NULL CONSTRAINT DF_Inv360ToolEv_At DEFAULT (SYSUTCDATETIME()),
            NoteText NVARCHAR(500) NULL,
            CreatedByUserID INT NULL,
            CONSTRAINT FK_Inv360ToolEv_Tool FOREIGN KEY (ToolAssetID) REFERENCES dbo.Inv360ToolsAssets(ToolAssetID)
        );
    END;

    IF OBJECT_ID(N'dbo.Inv360AppAudit', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.Inv360AppAudit (
            AppAuditID BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            EntityType NVARCHAR(60) NOT NULL,
            EntityID NVARCHAR(60) NOT NULL,
            EventName NVARCHAR(80) NOT NULL,
            EventNote NVARCHAR(1000) NULL,
            ActorUserID INT NULL,
            ActorUsername NVARCHAR(50) NULL,
            CreatedAt DATETIME2 NOT NULL CONSTRAINT DF_Inv360AppAudit_Created DEFAULT (SYSUTCDATETIME())
        );
    END;

    IF OBJECT_ID(N'dbo.Inv360Settings', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.Inv360Settings (
            SettingKey NVARCHAR(80) NOT NULL PRIMARY KEY,
            SettingValue NVARCHAR(500) NULL,
            UpdatedAt DATETIME2 NOT NULL CONSTRAINT DF_Inv360Set_Upd DEFAULT (SYSUTCDATETIME())
        );
    END;

    /* Ensure OWNER_ADMIN role label exists when Roles table is writable */
    IF COL_LENGTH(N'dbo.Roles', N'RoleName') IS NOT NULL
       AND NOT EXISTS (SELECT 1 FROM dbo.Roles WHERE RoleName = N'OWNER_ADMIN')
    BEGIN
        BEGIN TRY
            INSERT INTO dbo.Roles (RoleName, DisplayNameFa, IsActive)
            VALUES (N'OWNER_ADMIN', N'مالک / مدیر کامل', 1);
        END TRY
        BEGIN CATCH
            /* RoleID may be identity-less / constrained; ignore seed failure */
        END CATCH
    END;

    COMMIT TRANSACTION;
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION;
    THROW;
END CATCH;
