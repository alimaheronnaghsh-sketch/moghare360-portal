CREATE TABLE IF NOT EXISTS portal_jobcards (
    id INT AUTO_INCREMENT PRIMARY KEY,

    service_request_id INT NOT NULL,
    contract_confirmation_id INT DEFAULT NULL,

    jobcard_code VARCHAR(50) NOT NULL,
    mobile VARCHAR(30) NOT NULL,

    customer_name VARCHAR(200) DEFAULT NULL,
    vehicle_title VARCHAR(200) DEFAULT NULL,
    plate_number VARCHAR(80) DEFAULT NULL,
    vin VARCHAR(30) DEFAULT NULL,
    odometer_km INT DEFAULT NULL,

    service_type VARCHAR(150) DEFAULT NULL,
    current_status VARCHAR(80) NOT NULL DEFAULT 'JOBCARD_CREATED',

    assigned_team VARCHAR(150) DEFAULT NULL,
    assigned_technician VARCHAR(150) DEFAULT NULL,
    technical_manager VARCHAR(150) DEFAULT NULL,

    intake_note TEXT DEFAULT NULL,
    internal_note TEXT DEFAULT NULL,

    created_by VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY ux_jobcard_service_request (service_request_id),
    UNIQUE KEY ux_jobcard_code (jobcard_code),
    INDEX ix_jobcard_mobile (mobile),
    INDEX ix_jobcard_status (current_status),
    INDEX ix_jobcard_created_at (created_at)
);

CREATE TABLE IF NOT EXISTS portal_jobcard_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,

    jobcard_id INT NOT NULL,
    service_request_id INT NOT NULL,

    old_status VARCHAR(80) DEFAULT NULL,
    new_status VARCHAR(80) NOT NULL,

    changed_by VARCHAR(100) DEFAULT NULL,
    changed_role VARCHAR(100) DEFAULT NULL,
    change_note TEXT DEFAULT NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX ix_status_jobcard (jobcard_id),
    INDEX ix_status_request (service_request_id),
    INDEX ix_status_new_status (new_status),
    INDEX ix_status_created_at (created_at)
);

CREATE TABLE IF NOT EXISTS portal_jobcard_diagnosis_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,

    jobcard_id INT NOT NULL,
    service_request_id INT NOT NULL,

    report_type VARCHAR(80) NOT NULL DEFAULT 'DIAGNOSIS',
    report_title VARCHAR(250) DEFAULT NULL,

    diagnosis_summary TEXT DEFAULT NULL,
    fault_codes TEXT DEFAULT NULL,
    technician_note TEXT DEFAULT NULL,
    internal_note TEXT DEFAULT NULL,
    customer_visible_note TEXT DEFAULT NULL,

    estimated_labor_cost DECIMAL(18,2) DEFAULT 0,
    estimated_parts_cost DECIMAL(18,2) DEFAULT 0,
    estimated_total_cost DECIMAL(18,2) DEFAULT 0,

    customer_approval_status VARCHAR(80) NOT NULL DEFAULT 'NOT_REQUIRED',

    created_by VARCHAR(100) DEFAULT NULL,
    approved_by VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    approved_at DATETIME DEFAULT NULL,

    INDEX ix_diag_jobcard (jobcard_id),
    INDEX ix_diag_request (service_request_id),
    INDEX ix_diag_type (report_type),
    INDEX ix_diag_approval (customer_approval_status)
);

CREATE TABLE IF NOT EXISTS portal_jobcard_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,

    jobcard_id INT NOT NULL,
    service_request_id INT NOT NULL,
    diagnosis_report_id INT DEFAULT NULL,

    attachment_type VARCHAR(80) NOT NULL,
    file_title VARCHAR(250) DEFAULT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_mime VARCHAR(120) DEFAULT NULL,

    visible_to_customer TINYINT(1) NOT NULL DEFAULT 0,

    uploaded_by VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX ix_attach_jobcard (jobcard_id),
    INDEX ix_attach_request (service_request_id),
    INDEX ix_attach_report (diagnosis_report_id),
    INDEX ix_attach_type (attachment_type)
);

CREATE TABLE IF NOT EXISTS portal_jobcard_customer_approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,

    jobcard_id INT NOT NULL,
    service_request_id INT NOT NULL,
    diagnosis_report_id INT DEFAULT NULL,

    approval_type VARCHAR(80) NOT NULL,
    approval_title VARCHAR(250) DEFAULT NULL,
    approval_description TEXT DEFAULT NULL,

    requested_amount DECIMAL(18,2) DEFAULT 0,
    approval_status VARCHAR(80) NOT NULL DEFAULT 'PENDING',

    customer_response_note TEXT DEFAULT NULL,

    requested_by VARCHAR(100) DEFAULT NULL,
    responded_by VARCHAR(100) DEFAULT NULL,

    requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    responded_at DATETIME DEFAULT NULL,

    INDEX ix_approval_jobcard (jobcard_id),
    INDEX ix_approval_request (service_request_id),
    INDEX ix_approval_report (diagnosis_report_id),
    INDEX ix_approval_status (approval_status)
);

CREATE TABLE IF NOT EXISTS portal_jobcard_parts_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,

    jobcard_id INT NOT NULL,
    service_request_id INT NOT NULL,
    diagnosis_report_id INT DEFAULT NULL,

    part_name VARCHAR(250) NOT NULL,
    technical_code VARCHAR(150) DEFAULT NULL,
    requested_quantity DECIMAL(18,2) NOT NULL DEFAULT 1,

    estimated_unit_price DECIMAL(18,2) DEFAULT 0,
    estimated_total_price DECIMAL(18,2) DEFAULT 0,

    request_status VARCHAR(80) NOT NULL DEFAULT 'REQUESTED',

    requested_by VARCHAR(100) DEFAULT NULL,
    approved_by VARCHAR(100) DEFAULT NULL,
    purchased_by VARCHAR(100) DEFAULT NULL,

    requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    approved_at DATETIME DEFAULT NULL,
    purchased_at DATETIME DEFAULT NULL,

    INDEX ix_parts_jobcard (jobcard_id),
    INDEX ix_parts_request (service_request_id),
    INDEX ix_parts_report (diagnosis_report_id),
    INDEX ix_parts_status (request_status),
    INDEX ix_parts_code (technical_code)
);
