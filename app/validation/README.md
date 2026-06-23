# MOGHARE360 — Validation Engine Scaffold (`app/validation/`)

## Purpose

Future Validation Engine for MOGHARE360 ERP. Enforces business rules before Workflow Engine and database writes.

## Status

- **Not active runtime**
- Scaffold only — no production activation
- **No direct database write** — validation only; persistence via Workflow Engine

## Pipeline

**UI → Validation Engine → Workflow Engine → Database → Audit Log**

## Future Rules (Planned)

### Customer
- National ID Iran algorithm
- Mobile `09XXXXXXXXX`
- Persian-only name

### Vehicle
- Iran plate standard, VIN ISO 3779, engine/chassis, brand/model/class

### Media
- Max 6 input images, max 8 output images
- **Camera direct only**
- **No upload bypass**

### Diagnostics
- Initial PDF, Secondary PDF, Final PDF

## Product Boundary

- No official accounting activation
- No payment gateway/billing/tax integration created
