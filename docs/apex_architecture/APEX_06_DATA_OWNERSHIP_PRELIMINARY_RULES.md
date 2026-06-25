# APEX 06 — Preliminary Data Ownership Rules

## Scope

These are **preliminary** data ownership rules for ApexMahinERP Phase 0. A full **Data Ownership Matrix** is a pending deliverable (see APEX 07).

Physical schema must define ownership per aggregate/entity when schema design begins — **after** sign-off.

---

## Universal Ownership Principles

| Principle | Rule |
|-----------|------|
| Domain ownership | Each domain owns its aggregates and enforces invariants |
| Controlled references | Cross-domain references use stable IDs or codes via contracts |
| No direct mutation | A domain must not UPDATE/DELETE another domain’s owned data |
| Service/API only | Cross-domain interaction through published commands and queries |
| Single write authority | Each aggregate has exactly one owning domain for writes |

---

## Per-Domain Ownership Summary

| Domain | Owns (Write Authority) | May Reference (Read via API) |
|--------|------------------------|------------------------------|
| Organization | Tenant, Branch | User (Identity) |
| Identity & Access | User, Role, Permission | Branch (Organization) |
| Finance | Account, JournalEntry, LedgerEntry, BankAccount, Payment, CreditProfile | Party, JobCard (as source document refs) |
| Procurement | RFQ, PurchaseOrder, GRN, PurchaseInvoice, VendorRating | Supplier, Item |
| Inventory | Item, Warehouse, StockLedger, ReorderPolicy | Supplier (Procurement) |
| CRM & Marketing | Lead, Campaign, Appointment, SourceAttribution, Customer view | Party |
| HR | Skill, Attendance, TechnicianPerformance, BonusRule, Employee view | User, JobCard metrics |
| Job & Technical Intelligence | JobCard, JobStep, QCChecklist, WarrantyRecord, CaseRecord, Symptom, RootCause, RepairProcedure, FailurePattern, SuggestionRule | Customer, Item, Payment commands |

---

## Cross-Domain Reference Rules

| Pattern | Allowed | Forbidden |
|---------|---------|-----------|
| JobCard holds `customer_ref` | Yes — reference ID from CRM | CRM table JOIN in Job repository |
| Finance posts from JobCard event | Yes — via Finance command API | Job module INSERT into ledger |
| Inventory issues parts for JobCard | Yes — via Inventory issue command | Job module UPDATE stock quantity |
| Intelligence reads closed cases | Yes — via read API or event | Intelligence UPDATE JobCard status |

---

## Finance Isolation Rule

> **Finance must not be polluted by operational writes.**

- Only Finance Domain services post journal entries and ledger lines
- Job, Inventory, and Procurement domains emit **commands or events**; Finance translates them into accounting entries
- Operational tables must not contain debit/credit columns that bypass the ledger

---

## Job Domain Isolation Rule

> **Job domain must not directly mutate inventory or finance.**

- Part consumption → Inventory `issue` command
- Prepayment / delivery payment → Finance `payment` command
- QC pass/fail → Job domain state only; downstream effects via events

---

## Technical Intelligence Read-Only Posture

> **Technical Intelligence reads from operations but must not own operational truth.**

| Owns | Does Not Own |
|------|--------------|
| CaseRecord, Symptom, RootCause, RepairProcedure | JobCard lifecycle state |
| FailurePattern, SuggestionRule | Stock levels |
| Aggregated statistics | Ledger balances |

Intelligence may **suggest**; Job domain **decides** operational outcomes.

---

## Future Physical Schema Requirement

When physical schema design starts:

1. Every table maps to exactly one owning domain
2. Cross-domain FKs require documented contract and anti-corruption layer
3. Shared “utility” tables without an owner are forbidden
4. Migration scripts are domain-scoped where possible

---

## Cursor Statement

Cursor documented preliminary ownership rules only. **Cursor did not decide the next roadmap step.**
