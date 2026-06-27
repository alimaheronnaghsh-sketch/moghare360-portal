# Session Context Design

Project: MOGHARE360 ERP
Mission: Mission 7
Document Type: Session Context Design
Scope: Design Documentation Only

## Session Start Rule
All future protected admin pages should start session through one approved Auth Context helper.

Future rule:
- Session must be started once
- Session start must be centralized
- Pages must not duplicate inconsistent session logic

## Required Session Keys
Future session context may include:

- erp_user_id
- erp_username
- erp_login_timestamp
- erp_last_activity_timestamp
- erp_session_regenerated_at

## Current User ID
current_user_id must come from the approved session key.

## Username
username must come from the approved session key or trusted current user lookup.

## Login Timestamp
login timestamp should be recorded when user logs in.

## Last Activity Timestamp
last activity timestamp should update through controlled future logic.

## Timeout Placeholder
Timeout is a future security control.
Mission 7 does not implement timeout.

## Logout Cleanup
Logout must clear all ERP session keys.

## Session Regeneration Rule
Session ID should be regenerated after successful login in the future Auth Context implementation.

## Mission 7 Boundary
No session code is created in Mission 7.
