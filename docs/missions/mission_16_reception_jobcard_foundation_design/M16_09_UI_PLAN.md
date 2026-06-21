# UI Plan

## Purpose
This document defines the future UI plan for JobCard foundation.

## Future UI Pages
Possible Mission 17 UI files:

- public_html/erp-jobcard-create.php
- public_html/erp-jobcard-readonly-list.php
- public_html/erp-jobcard-detail.php

## Create UI Sections
Future create page should include:

1. Customer / Vehicle selection
2. Customer / Vehicle relation confirmation
3. Reception information
4. Intake mileage
5. Customer complaint
6. Requested services summary
7. Initial vehicle condition
8. Internal notes
9. Create result

## Read-Only List UI
Future list page should show:

- jobcard_id
- jobcard_number
- customer name
- vehicle brand/model
- plate number or VIN
- jobcard_status
- reception_at
- created_at

## Detail UI
Future detail page should show:

- JobCard header
- Customer summary
- Vehicle summary
- Reception data
- Status
- History timeline

## Permission Boundary
Future UI must require:
- Auth Context
- Permission Guard
- CSRF for writes
- Audit/history strategy for writes

## Mission 16 Boundary
No UI file is created in Mission 16.
