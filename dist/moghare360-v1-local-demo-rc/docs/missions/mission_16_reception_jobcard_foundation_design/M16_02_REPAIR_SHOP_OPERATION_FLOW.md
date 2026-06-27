# Repair Shop Operation Flow

## Purpose
This document defines the operational flow that the JobCard foundation must support.

## High-Level Flow
The intended repair shop flow:

1. Customer / Vehicle identification
2. Reception
3. Initial vehicle condition note
4. JobCard creation
5. Technical diagnosis
6. Service operation planning
7. Parts request if needed
8. Approval if needed
9. Work in progress
10. Quality control
11. Delivery preparation
12. Delivery / closure

## Mission 16 Boundary
Mission 16 only designs the foundation up to JobCard creation.

It does not implement:
- diagnosis execution
- parts request
- approval workflow
- service task execution
- QC execution
- delivery
- invoice / payment

## Soft Run Reception Goal
The first JobCard prototype must capture:
- customer
- vehicle
- relation
- reception date/time
- intake mileage
- complaint / requested service
- initial status
- internal notes

## Final Flow Decision
Mission 17 should create only the controlled JobCard foundation and must not enter service operation, inventory, or finance scope.
