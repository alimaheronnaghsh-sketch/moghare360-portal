# Customer Data Model Plan

## Purpose
This document defines the planned Customer foundation for MOGHARE360 ERP.

## Proposed Customer Entity
Customer should represent the person or organization receiving service.

## Proposed Fields
Suggested Customer fields:

- customer_id
- customer_code
- customer_type
- first_name
- last_name
- full_name
- national_id
- economic_code
- primary_mobile
- secondary_mobile
- email
- address
- city
- notes
- lifecycle_state
- created_at
- updated_at
- created_by_user_id
- updated_by_user_id

## Customer Type
Possible values:
- PERSON
- COMPANY

## Lifecycle State
Possible values:
- ACTIVE
- INACTIVE
- BLOCKED
- MERGED

## Phone / Contact Foundation
Customer phones should support:
- multiple phone numbers
- primary phone flag
- phone type
- verification placeholder
- do-not-contact placeholder

## Soft Run Minimum Fields
Minimum required Customer fields for Soft Run:
- full_name
- primary_mobile
- lifecycle_state

## Security Boundary
Customer creation in Mission 15 must use:
- Auth Context
- Permission Guard
- CSRF
- Audit or history strategy

## Mission 14 Boundary
This is a design plan only.
No SQL is executed.
