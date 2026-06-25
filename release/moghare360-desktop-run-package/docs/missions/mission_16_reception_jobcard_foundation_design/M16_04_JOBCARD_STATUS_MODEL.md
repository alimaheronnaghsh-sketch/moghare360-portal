# JobCard Status Model

## Purpose
This document defines the initial JobCard status model.

## Initial Statuses
The initial JobCard statuses are:

1. DRAFT
2. RECEIVED
3. IN_PROGRESS
4. WAITING_PARTS
5. QC_READY
6. DELIVERED
7. CLOSED
8. CANCELLED

## Status Definitions

### DRAFT
JobCard has been started but not formally received.

### RECEIVED
Vehicle reception is confirmed and JobCard is active.

### IN_PROGRESS
Technical work has started.

### WAITING_PARTS
Work is blocked due to required parts.

### QC_READY
Work is ready for quality control.

### DELIVERED
Vehicle has been delivered to customer.

### CLOSED
JobCard is financially and operationally closed.

### CANCELLED
JobCard was cancelled before completion.

## Mission 17 Allowed Status
Mission 17 should only create JobCard with:
- DRAFT
or
- RECEIVED

## Forbidden in Mission 17
Mission 17 must not implement:
- IN_PROGRESS transition
- WAITING_PARTS transition
- QC_READY transition
- DELIVERED transition
- CLOSED transition
- CANCELLED transition

## Final Status Decision
Mission 16 locks the status model, but transition execution is deferred.
