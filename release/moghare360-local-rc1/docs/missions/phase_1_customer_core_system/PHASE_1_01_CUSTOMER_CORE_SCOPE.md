# PHASE 1 — Customer Core Scope

## Purpose

Transform the Customer Blueprint into a working internal foundation for:

1. Customer intake (ورود مشتری)
2. Customer contract (قرارداد مشتری)
3. Internal customer profile (پروفایل داخلی)
4. Vehicle-to-customer binding (اتصال خودرو)

## In Scope

- Six new `dbo.erp_*` tables (intakes, contracts, acceptances, bindings, photo records, history)
- Read-only dashboard
- Controlled create forms with separate submit handlers
- CSRF on all writes
- Auth + permission guard (existing helpers, placeholder actions)
- Legacy table read-only compatibility checks
- RTL Persian UI aligned with Soft Run

## Out of Scope / Forbidden

- Customer public portal
- Login / auth architecture changes
- Permission model changes
- Accounting, invoice, tax
- Tenant / SaaS / multi-company
- Real file upload / storage
- DROP / RENAME of existing tables
- Modifications to: `staff-auth.php`, `access-control.php`, `staff-login.php`, `config.php`, `config.example.php`, `private/*`

## Boundary

Phase 1 ends at controlled internal staff workflows. No customer self-service login. No financial posting.
