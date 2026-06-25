# Testing Plan

## Purpose
This document defines the future test plan for JobCard implementation.

## Future Tests

### 1. SQL Structure Test
Validate future JobCard tables, columns, constraints, indexes, and foreign keys.

### 2. Existing Customer / Vehicle Link Test
Confirm JobCard can link to existing Customer / Vehicle foundation records.

### 3. Create JobCard Test
Create controlled test JobCard.

### 4. Read-Only List Test
Confirm created JobCard is visible in internal list.

### 5. Detail Page Test
Confirm JobCard detail page shows customer, vehicle, reception data, status, and history.

### 6. Permission Guard Test
Confirm unauthorized users cannot create JobCards.

### 7. CSRF Test
Confirm write page requires CSRF.

### 8. History Test
Confirm JobCard creation writes history.

### 9. Forbidden Scope Test
Confirm no Service Operation, Inventory, Finance, Customer Portal, legacy, config, login, tenant, or workflow files are changed.

## Mission 16 Boundary
Testing is planned only.
No runnable test file is created in Mission 16.
