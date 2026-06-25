# V0 ERP Access Lifecycle Read-Only Dashboard Plan

## Project
MOGHARE360 ERP

## Purpose
Design the first read-only Access Lifecycle Dashboard for local ERP visibility without changing login, users, roles, permissions, or runtime behavior.

## Current Status
The following planning document already exists:

- docs/V0_ERP_ACCESS_LIFECYCLE_UI_PLAN.md

This document defines the next read-only dashboard implementation boundary.

## Suggested Future File
public_html/erp-access-lifecycle-readonly-dashboard.php

This file is not created in this step.

## Dashboard Type
Local-only diagnostic dashboard.

Read-only.

SELECT queries only.

No INSERT.
No UPDATE.
No DELETE.
No role assignment.
No user creation.
No login replacement.

## Data Sources

The future dashboard may read from:

- core_access_requests
- core_access_request_items
- core_access_approvals
- core_user_roles
- core_access_suspensions
- core_access_restrictions
- core_access_change_history
- core_audit_logs
- core_users
- core_roles
- core_permissions
- core_departments
- core_positions

## Dashboard Checks

### A01 Database Connection
Confirm PHP ODBC connection to moghare360_ERP works.

### A02 Access Request Table Exists
Confirm core_access_requests exists.

### A03 Access Request Items Table Exists
Confirm core_access_request_items exists.

### A04 Access Approvals Table Exists
Confirm core_access_approvals exists.

### A05 User Roles Table Exists
Confirm core_user_roles exists.

### A06 Access Suspensions Table Exists
Confirm core_access_suspensions exists.

### A07 Access Restrictions Table Exists
Confirm core_access_restrictions exists.

### A08 Access Change History Table Exists
Confirm core_access_change_history exists.

### A09 Audit Logs Table Exists
Confirm core_audit_logs exists.

### A10 Total Access Requests
Show total count of access requests.

### A11 Pending Access Requests
Show count of pending access requests.

### A12 Applied Access Requests
Show count of applied access requests.

### A13 Emergency Access Requests
Show count of emergency access requests.

### A14 Total Approval Records
Show count of access approval records.

### A15 Pending Approval Records
Show count of pending approval records.

### A16 Active User Roles
Show count of active user role records.

### A17 Active Suspensions
Show count of active access suspensions.

### A18 Active Restrictions
Show count of active access restrictions.

### A19 Access History Count
Show count of access change history records.

### A20 Access Audit Count
Show count of access-related audit log records.

## Page Sections

### Section 1: System Status
Shows:
- Database name
- Connection status
- Dashboard mode
- Read-only status

### Section 2: Access Request Summary
Shows:
- Total requests
- Pending requests
- Applied requests
- Emergency requests

### Section 3: Approval Summary
Shows:
- Total approvals
- Pending approvals
- Approved approvals
- Rejected approvals

### Section 4: Current Access Summary
Shows:
- Active user roles
- Active suspensions
- Active restrictions

### Section 5: History and Audit Summary
Shows:
- Access change history count
- Audit log count

### Section 6: Safety Confirmation
Shows:
- No login logic changed
- No config secret displayed
- No password hash displayed
- No user creation performed
- No role assignment performed
- SELECT only

## Local URL After Future Implementation
http://localhost:8080/moghareh360/erp-access-lifecycle-readonly-dashboard.php

## Implementation Boundary

Allowed in future implementation:
- One local-only PHP diagnostic page
- ODBC connection
- SELECT queries only
- Summary counts
- Safety status display

Not allowed:
- Login replacement
- staff-auth.php modification
- access-control.php modification
- config.php modification
- config.example.php modification
- User creation
- Role assignment
- Permission assignment
- Access approval action
- Access apply action
- SQL migration
- Production deployment

## Test Codes
The future dashboard must show A01 through A20.

Expected result:

A01 to A20 = OK

Overall Status = OK

## Final Decision
This is a planning-only document.

No executable dashboard is created in this step.
No login replacement is approved.
No user creation is approved.
No role assignment is approved.
No SQL migration is approved.
