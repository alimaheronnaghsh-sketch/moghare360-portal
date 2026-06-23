# Auth Context Purpose and Scope

Project: MOGHARE360 ERP
Mission: Mission 7
Document Type: Auth Context Purpose and Scope
Scope: Design Documentation Only

## What Auth Context Means
Auth Context is the controlled runtime identity layer that tells every ERP admin page who the current user is, what roles the user has, what permissions are available, and whether the request is allowed.

## Why Auth Context Is Required Before Real Assignment
Real Assignment must not start until identity, role, permission, and session boundaries are centralized. Without Auth Context, each page could guess the current user, role, or permission differently.

## Why Pages Must Not Guess User / Role / Permission
Every page must use one approved Auth Context source. Pages must not independently query or assume:
- current user
- active roles
- permission keys
- system owner fallback
- session validity

## Goal in MOGHARE360
The goal is to create a future-safe identity boundary before Real Assignment, Tenant isolation, production login, or permission enforcement expands.

## Mission 7 Scope
Mission 7 is design only:
- No PHP file
- No SQL file
- No login change
- No role assignment
- No permission change
