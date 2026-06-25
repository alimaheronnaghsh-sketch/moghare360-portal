# MOGHARE360 — Validation Error Policy

**Status:** Locked planning baseline — Documentation only

---

## Error Categories

| Code | Category | Description |
|------|----------|-------------|
| E-01 | **REQUIRED_FIELD_MISSING** | Mandatory field null or empty |
| E-02 | **INVALID_FORMAT** | National ID, mobile, VIN, plate, date format fail |
| E-03 | **DUPLICATE_RISK** | Unique constraint or business duplicate detected |
| E-04 | **INVALID_STATE_TRANSITION** | Workflow transition not in allowed matrix |
| E-05 | **PERMISSION_DENIED** | Role/permission check failed |
| E-06 | **CROSS_DOMAIN_WRITE_BLOCKED** | Module attempted write outside ownership |
| E-07 | **MEDIA_RULE_VIOLATION** | Camera/upload rule broken |
| E-08 | **DATABASE_WRITE_BLOCKED** | Write rejected before DB (validation/workflow) |
| E-09 | **AUDIT_REQUIRED** | Audit append failed — rollback write |
| E-10 | **PRODUCTION_BOUNDARY_BLOCKED** | SaaS, portal, accounting, payment gateway action |

---

## Error Response Principles

| Principle | Rule |
|-----------|------|
| **Clear user-facing message** | Persian RTL; field-level errors where possible |
| **Internal diagnostic note** | Technical code (E-01…) in server log only |
| **No sensitive config exposure** | Never return DB connection, `erp-config`, paths |
| **No stack trace to user** | Generic error for unexpected failures |
| **Audit event where relevant** | `permission_denied`, `validation_failed` logged |

---

## Category Handling

### REQUIRED_FIELD_MISSING (E-01)

- Return field name and requirement to UI
- Do not partial-write to database

### INVALID_FORMAT (E-02)

Includes: **National ID**, **Mobile** (`09XXXXXXXXX`), **VIN**, **Plate**, status enums

- Return specific format hint (not algorithm internals to user)

### MEDIA_RULE_VIOLATION (E-07)

| Violation | Response |
|-----------|----------|
| File upload attempted | Reject — **No upload bypass** |
| Non-camera source | Reject — **Camera direct only** |
| Image count exceeded | Reject with max count |

### PRODUCTION_BOUNDARY_BLOCKED (E-10)

| Blocked action | Message concept |
|----------------|-----------------|
| Official accounting post | Preview only — **official accounting not active** |
| Payment gateway charge | **Payment gateway not active** |
| Public portal write | **Public customer portal not active** |
| SaaS billing | **Production SaaS not active** |

---

## Error Flow Position

```
UI → Validation Engine [errors E-01..E-07, E-08] → stop
     → Permission check [E-05] → stop
     → Workflow Engine [E-04] → stop
     → Database
     → Audit [E-09 if fail → rollback]
```

**UI → Validation Engine → Workflow Engine → Database → Audit Log**

---

## Cross-Domain Write Errors (E-06)

When module A attempts direct write to module B owned table:

- Block before database
- Log `cross_domain_write_blocked` audit event
- Return user message: operation not allowed on this record

---

## Product Boundary

- Error policy — planning for future `app/validation/` implementation
- No PHP error handlers modified in Phase 07

---

**END OF VALIDATION ERROR POLICY**
