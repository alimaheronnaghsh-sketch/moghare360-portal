# Mission 8 Testing Plan

Project: MOGHARE360 ERP
Mission: Mission 8
Document Type: Testing Plan
Scope: Auth Context Helper Implementation

## CLI Test Plan

File:
tools/test-erp-auth-context.php

Steps:
1. Load config loader
2. Load auth context helper
3. Connect with PDO ODBC to SQLEXPRESS / moghare360_ERP
4. Resolve current user id = 10001
5. Load user record
6. Load roles
7. Load permissions
8. Test is_system_owner
9. Test access.request.apply
10. Test access.request.approve

Expected output:
- M08 CLI AUTH CONTEXT TEST
- User: 10001 / mahin.paradigm.owner
- Roles: owner, system_admin
- Permissions loaded: [count > 0]
- is_system_owner: OK
- can access.request.apply: OK
- can access.request.approve: OK
- Overall: OK

## Browser Test Plan

File:
public_html/erp-auth-context-readonly-test.php

Steps:
1. Open browser URL after runtime copy
2. Confirm warning banner is visible
3. Confirm connection status = OK
4. Confirm user_id = 10001
5. Confirm username = mahin.paradigm.owner
6. Confirm roles include owner and system_admin
7. Confirm permissions count > 0
8. Confirm access.request.approve = OK
9. Confirm access.request.apply = OK
10. Confirm tenant placeholder values
11. Confirm Overall Status = OK

## Security Test Plan
- Confirm no password_hash is displayed
- Confirm no config secrets are displayed
- Confirm page has no form
- Confirm page has no POST handling
- Confirm meta noindex, nofollow exists
- Confirm helper performs SELECT only

## Forbidden File Test Plan
Confirm Mission 8 did not modify:
- staff-auth.php
- access-control.php
- config.php
- config.example.php
- Customer Portal files
- legacy login files outside approved Mission 8 scope

## Expected Results
- PHP syntax valid
- CLI Overall: OK
- Browser Overall Status: OK
- Forbidden files unchanged
- No database writes performed
