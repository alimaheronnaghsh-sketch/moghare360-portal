# Permission Naming Alignment

## Current Known Permission Examples
Known permission examples:

- access.request.approve
- access.request.apply

## Possible Naming Mismatch
Possible mismatch:

- access_request.submit

## Preferred Naming
Preferred production naming:

- dot-separated permission keys
- module.entity.action style

Example:
access.request.submit

## Reuse Option
If submit remains part of create flow, reuse:

access.request.create

## Rule
Production permission keys should use dot-separated keys.

No underscore action keys for production.

## Future Mission Requirement
A future mission must decide whether submit uses:
- access.request.submit
- access.request.create

## Mission 9 Boundary
No permission key is changed.
No permission is created.
No role_permission row is changed.
