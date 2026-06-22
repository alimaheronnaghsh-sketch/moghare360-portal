# Mission 24 - Testing Plan

## Prerequisites
1. Missions 17, 20, 22 SQL executed
2. M24 SQL executed (includes optional SEED)
3. JobCard id=1, part id=1, MAIN location exist

## Tests
1. SQL tables exist
2. Browser part use for JobCard 1 — JobCard Part Usage Created OK
3. List page shows usage
4. History JOBCARD_PART_USED
5. ISSUE movement with JOBCARD_PART_USAGE reference
6. Stock not negative after usage
7. Insufficient stock rejected (optional manual test)
8. CLI Overall OK
9. No finance / invoice / payment / purchase / delivery writes

## Status
PENDING USER TEST (M24_90)
