# Implementation Boundary

## New Files Only
- assets/moghare360-ui/moghare360-shell.css
- assets/moghare360-ui/moghare360-shell.js
- includes/moghare360-ui-shell.php
- erp-app-shell-demo.php
- Mission 32 docs

## Forbidden Changes
- config.php, staff-auth.php, access-control.php
- Customer Portal / legacy portal
- Existing ERP operational PHP pages (M17–M30 prototypes unchanged)
- SQL scripts
- Permission seed / auth tables

## PHP Include Rules
- Render HTML only
- No ODBC / database calls
- No session auth modification
- Role menu = placeholder array filter

## JS Rules
- UI behavior only
- No fetch/XHR to API
- No form submit handlers that write

## Final Boundary Decision
Additive shell layer only; zero backend impact.
