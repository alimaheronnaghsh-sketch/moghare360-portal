# UI Plan

## Purpose
This document defines the future UI plan for Customer / Vehicle foundation.

## Future UI Pages
Possible Mission 15 UI files:

- public_html/erp-customer-vehicle-create.php
- public_html/erp-customer-vehicle-readonly-list.php

## Create UI Sections
Future create page should include:

1. Customer data
2. Customer phone/contact data
3. Vehicle data
4. Customer vehicle relation
5. Save result / validation feedback

## List UI Sections
Future list page should show:

- customer name
- mobile
- vehicle brand
- vehicle model
- plate number or VIN
- relation type
- lifecycle state
- created_at

## Permission Boundary
Future UI must require:
- Auth Context
- Permission Guard
- CSRF for writes
- Audit/history strategy for writes

## Mission 14 Boundary
No UI file is created in Mission 14.
