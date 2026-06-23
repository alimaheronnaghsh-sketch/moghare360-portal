# Mission 8 Implementation Plan

Project: MOGHARE360 ERP
Mission: Mission 8
Document Type: Implementation Plan
Scope: Auth Context Helper Implementation

## Why Auth Context Helper Is Needed
MOGHARE360 ERP currently has multiple prototype pages that each resolve user, role, and permission context differently. Mission 8 centralizes identity loading into one approved helper before Real Assignment, tenant isolation, or production login expansion.

## What It Centralizes
Mission 8 centralizes:
- session start
- current user id resolution
- current user record loading
- active role loading
- active permission loading
- system owner detection
- permission checks
- tenant placeholder context
- logout session key list

## Why It Comes Before Real Assignment
Real Assignment must not begin until the system can answer these questions consistently:
- who is the current user
- which roles are active
- which permissions are active
- whether the user is Platform Owner

Mission 8 provides that read-only foundation without performing assignment writes.

## Why It Does Not Replace Login Yet
Mission 8 does not modify:
- erp-admin-login.php
- staff-auth.php
- access-control.php

Mission 8 uses controlled local fallback user_id = 10001 only when no approved ERP session key exists. This is for local read-only testing, not production authentication.

## Mission 8 Boundary
- SELECT only
- No login replacement
- No role assignment
- No permission mutation
- No workflow write
