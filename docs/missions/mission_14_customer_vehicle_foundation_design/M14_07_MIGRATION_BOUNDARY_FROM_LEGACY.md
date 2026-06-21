# Migration Boundary From Legacy

## Purpose
This document defines what is and is not allowed regarding legacy customer data.

## Current Decision
No migration is executed in Mission 14.

## Allowed
Mission 14 may document:
- possible legacy source names
- possible future mapping concepts
- data quality risks
- migration strategy placeholder

## Forbidden
Mission 14 must not:
- copy legacy files
- modify Customer Portal
- import customer data
- create migration SQL
- execute migration SQL
- change production-like customer records

## Future Migration Rules
A future migration mission must define:
- source table/file
- target table
- field mapping
- duplicate detection
- rollback plan
- audit strategy
- test-only dry run

## Final Decision
Legacy migration is deferred.
