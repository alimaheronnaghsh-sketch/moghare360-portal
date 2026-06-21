# MOGHARE360 ERP - V0 Foundation Lock Report

## 1. Phase
Current phase:
Core ERP Foundation + Controlled Admin Prototype

## 2. Locked Database Status
- Database: moghare360_ERP
- SQL Server Instance: SQLEXPRESS
- Collation: Persian_100_CI_AS
- Core Tables: 16
- Departments: 14
- Positions: 43
- Roles: 18
- Permissions: 43
- Role Permissions: 162
- Approval Rules: 16
- Customer Roles: 0
- Access Requests: 2

## 3. Locked Platform Owner
- user_id: 10001
- username: mahin.paradigm.owner
- roles: owner + system_admin

## 4. Locked Runtime Path
- Local URL: http://localhost:8080/moghare360/
- Runtime folder: C:\xampp\htdocs\moghare360
- Current local naming: moghare360
- Future brand naming can be configurable, including moghareh360

## 5. Existing Controlled Pages
- erp-bootstrap-status.php
- erp-admin-readonly-dashboard.php
- erp-access-lifecycle-readonly-dashboard.php
- erp-admin-login.php
- erp-admin-dashboard.php
- erp-admin-logout.php
- erp-admin-protected-test.php
- erp-access-request-create.php
- erp-access-request-list.php
- erp-access-request-detail.php

## 6. Locked Rules
- Diagnostic pages must remain SELECT-only.
- Dashboard pages must remain read-only.
- No direct role assignment from UI.
- No direct permission changes from UI.
- No customer portal changes.
- No tenant changes.
- No production deployment.
- First real write must pass through:
  Browser Form → CSRF Validation → Auth Check → Permission Check → Workflow Engine → Audit / History → State Update

## 7. Locked Forbidden Files
These files must not be modified without explicit approval:
- config.php
- config.example.php
- staff-auth.php
- access-control.php

## 8. Sign-Off
V0 Foundation is locked for controlled continuation into Foundation hardening and Access Request Workflow.
