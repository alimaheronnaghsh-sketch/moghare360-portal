# V0 ERP Access Lifecycle UI Plan

## Project
MOGHARE360 ERP

## Purpose
Design the first read-only and request-based UI plan for ERP access lifecycle management without changing the current login system.

## Current Rule
V0 does not replace the existing login system.

The current PHP login, staff-auth.php, access-control.php, config.php, and config.example.php must not be changed in this phase.

## Scope of This Plan
This plan only defines the future UI structure for managing access requests.

No implementation is allowed in this step.

## Access Lifecycle Flow

### 1. Access Request Creation
A manager or authorized operator can create an access request for a staff member.

The request may include:
- User identity
- Department
- Position
- Requested role
- Requested permissions
- Reason
- Start date
- End date
- Emergency flag

### 2. Access Request Review
The system shows pending access requests to authorized approvers.

The approver can see:
- Request number
- Request type
- Staff name
- Department
- Position
- Requested roles
- Requested permissions
- Reason
- Current approval state

### 3. Approval Flow
Approval must follow the rules already seeded in core approval rules.

No direct role assignment is allowed from UI.

### 4. Access Application
After all required approvals are completed, the system may apply access changes.

This phase is not implemented in V0 UI.

### 5. Suspension and Restriction
Future UI must support:
- Temporary suspension
- Partial restriction
- Emergency lock
- Reason logging
- Audit logging

### 6. History and Audit
Every access change must be visible in read-only history.

The UI must show:
- Request history
- Approval history
- Change history
- Audit logs

## V0 UI Pages

### Page 1: Access Request List
Status: Plan Only

Purpose:
Show all access requests.

Filters:
- Request state
- Department
- Position
- Request type
- Date range

Actions:
- View details only in V0

### Page 2: Access Request Detail
Status: Plan Only

Purpose:
Show full request information.

Sections:
- Request summary
- Requested roles
- Requested permissions
- Approval status
- Audit trail

Actions:
- No approve action in V0
- No reject action in V0
- No apply action in V0

### Page 3: Approval Queue
Status: Plan Only

Purpose:
Show pending approvals based on seeded approval rules.

Actions:
- View only in V0

### Page 4: Access History
Status: Plan Only

Purpose:
Show access change history.

Actions:
- View only in V0

### Page 5: Access Audit Log
Status: Plan Only

Purpose:
Show audit logs related to access lifecycle.

Actions:
- View only in V0

## V0 Implementation Boundary

Allowed later:
- Read-only UI pages
- SELECT queries only
- Local diagnostic pages
- No login replacement
- No user creation
- No role assignment
- No permission assignment

Not allowed:
- Changing login
- Changing staff-auth.php
- Changing access-control.php
- Changing config.php
- Changing config.example.php
- Creating users
- Assigning roles
- Migrating staff_users
- Applying access changes from UI

## Suggested First Implementation After This Plan
Build a read-only Access Lifecycle Dashboard.

Suggested file:
public_html/erp-access-lifecycle-readonly-dashboard.php

This file must be local-only and read-only.

## Final Decision
This document is approved only as a UI planning document.

No executable access lifecycle UI is created in this step.
No login replacement is approved.
No user creation is approved.
No role assignment is approved.
