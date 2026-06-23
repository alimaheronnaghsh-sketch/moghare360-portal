# MOGHARE360 — Execution Control Registry

**Status:** Active control document  
**SQL:** No SQL required for registry maintenance

---

## Roles

| Role | Responsibility |
|------|----------------|
| **ChatGPT** | Master controller — defines phase scope, missions, boundaries, and approval for commits |
| **Cursor** | Implementation executor — creates only what missions authorize within allowed scope |
| **User** | Bridge between ChatGPT, Cursor, GitHub, SQL (SSMS), and PowerShell |

---

## Execution Rules

1. **Scope lock** — Cursor must not exceed ALLOWED SCOPE per phase prompt.
2. **Forbidden lock** — Cursor must not touch FORBIDDEN SCOPE items (auth, config secrets, `public_html` runtime unless phased).
3. **SQL execution** — SQL is executed **only by User** in database `moghare360_ERP` when ChatGPT explicitly provides scripts for a controlled phase. Cursor does not run SSMS.
4. **GitHub** — Commit and push **only after ChatGPT approval**. User performs git operations when approved.
5. **Architecture** — All future writes follow:

**UI → Validation Engine → Workflow Engine → Database → Audit Log**

---

## Phase Lifecycle

```
ChatGPT defines phase → User pastes to Cursor → Cursor implements allowed scope
  → Cursor reports in standard format → User reviews with ChatGPT
  → Validation → ChatGPT approves commit (if any) → User commits/pushes
```

---

## Product Boundaries (Global)

- No production SaaS activation
- No public customer portal activation
- No official accounting activation
- No payment gateway/billing/tax integration created

---

## Reference Documents

- `docs/master/MOGHARE360_MASTER_EXECUTION_PROMPT_FINAL_LOCKED.md`
- `docs/master/MOGHARE360_CURSOR_EXECUTION_PACK_INDEX.md`
- `docs/control/MOGHARE360_FORBIDDEN_FILES_AND_ACTIONS.md`
- `docs/control/MOGHARE360_PHASE_REPORT_FORMAT.md`

---

**END OF REGISTRY**
