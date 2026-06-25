# APEX 13 — Finance Domain Logical Model

## Domain

**Finance Domain** — ledger truth, receivables, payables, cash, and credit control.

**Logical only. Not physical schema. No SQL.**

---

## Logical Entities

| Entity | Role |
|--------|------|
| **Account** | Chart of accounts entry |
| **JournalEntry** | Accounting journal document (header) |
| **LedgerEntry** | Posted debit/credit line |
| **BankAccount** | Cash or bank account |
| **Payment** | Payment or receipt transaction |
| **CreditProfile** | Credit limit and risk posture for a party |
| **Receivable** | Open customer receivable balance document |
| **Payable** | Open vendor payable balance document |

---

## Responsibilities

- Ledger truth — sole authority for posted accounting entries
- Accounts Receivable (AR) lifecycle
- Accounts Payable (AP) lifecycle
- Cash and bank movement recording
- Credit control and exposure limits
- Financial period close integrity

---

## Does Not Own

| Area | Owning Domain |
|------|---------------|
| JobCard technical and operational truth | Job & Technical Intelligence |
| Stock quantity truth | Inventory |
| Purchase order operational lifecycle | Procurement |
| Customer marketing journey | CRM & Marketing |

---

## Boundary Rule

> Other domains **request finance actions** through the service boundary.
> **No direct operational mutation** into the ledger from Job, Inventory, or Procurement code paths.

Typical pattern: domain emits `RegisterPaymentCommand` or `PostJournalFromSourceCommand`; Finance validates and posts.

---

## Logical Diagram

```mermaid
erDiagram
    Account ||--o{ LedgerEntry : posts_to
    JournalEntry ||--|{ LedgerEntry : contains
    BankAccount ||--o{ Payment : receives
    Payment }o--|| JournalEntry : may_generate
    CreditProfile ||--o| Receivable : limits
    CreditProfile ||--o| Payable : limits
    Receivable }o--|| JournalEntry : originated_from
    Payable }o--|| JournalEntry : originated_from

    JournalEntry {
        string journal_ref logical
        string source_domain logical
        string source_document_ref logical
    }
    Payment {
        string payment_ref logical
        string direction logical
        string status logical
    }
```

---

## Cross-Domain Finance Triggers (Service/API Only)

| Source Domain | Finance Action (logical) |
|---------------|--------------------------|
| Job | Prepayment receipt, delivery invoice, warranty credit |
| Procurement | Purchase invoice registration, vendor payment |
| CRM | Deposit or advance (via Job intake) |
| Inventory | Cost adjustment (Finance-approved command only) |

---

## Service Boundary Notes

| Exposed (preview) | Description |
|-------------------|-------------|
| `checkCredit(party_ref, amount)` | Credit availability |
| `registerPayment(command)` | Record payment/receipt |
| `postJournalFromSource(command)` | Post accounting from source document |
| `getReceivableBalance(party_ref)` | AR query |
| `getPayableBalance(party_ref)` | AP query |

Finance **never** updates JobCard status or stock quantities directly.

---

## Cursor Statement

**Cursor did not decide the next roadmap step.**
