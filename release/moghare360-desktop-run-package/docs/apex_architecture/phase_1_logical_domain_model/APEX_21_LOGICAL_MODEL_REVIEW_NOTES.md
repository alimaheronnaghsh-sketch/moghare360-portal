# APEX 21 — Logical Model Review Notes

## Purpose

Open questions, risks, and decisions needed **before Phase 2** (approval, ownership matrix, physical schema prep).

**Logical only. No physical schema until ownership matrix and service boundary map are approved.**

---

## Open Questions

| # | Question | Domains Affected |
|---|----------|------------------|
| 1 | Is **Party** a shared kernel or owned by CRM with references elsewhere? | CRM, Finance, Procurement, HR |
| 2 | Should **OrganizationUnit** be MVP or Phase 2? | Organization |
| 3 | Single **TechnicalIntelligenceService** host vs separate deployable? | Job & TI |
| 4 | Payroll: HR bonus accrual only, or full payroll in Finance MVP? | HR, Finance |
| 5 | Landed cost allocation: at GRN, at invoice, or both? | Procurement, Inventory, Finance |
| 6 | Cross-branch stock transfer policy? | Inventory, Organization |
| 7 | Warranty loop: separate aggregate or JobCard sub-state? | Job |
| 8 | Customer credit: Finance-only or mirrored read model in CRM? | Finance, CRM |

---

## Risks

| Risk | Impact | Mitigation |
|------|--------|------------|
| Party model ambiguity | Cross-domain reference chaos | Decide single owner in ownership matrix |
| Gate bypass in implementation | Prepayment/QC/delivery integrity loss | Enforce gates only via Job service commands |
| Intelligence writes to Job state | Operational truth corruption | TI advisory-only contract in service map |
| Finance pollution from operations | Ledger integrity failure | Command-only posting pattern locked in Phase 0/1 |
| Over-scoping OrganizationUnit | MVP delay | Defer if not needed for first branch |
| Service preview drift | Implementation inconsistency | Formalize API contracts in post-signoff phase |

---

## Decisions Needed Before Phase 2

| Decision | Owner | Blocks |
|----------|-------|--------|
| Architecture sign-off (Phase 0 + Phase 1) | Project Controller | All physical design |
| Party ownership model | Architecture review | CRM, Finance, Procurement references |
| Full data ownership matrix | Architecture review | Physical schema per domain |
| Formal service boundary map | Architecture review | API contract phase |
| MVP payroll scope | Product Controller | Finance + HR model depth |
| TI service deployment model | Technical architecture | Service topology |

---

## Phase 2 Prerequisites (Clean Restart)

Per clean restart plan, **no physical schema** until:

1. ✅ Freeze Architecture (Phase 0)
2. ✅ Design Logical Domain Model Diagram (Phase 1 — this package)
3. ☐ Approval & Sign-Off
4. ☐ Define Data Ownership Rules (full matrix)
5. ☐ Start Physical Schema Design

---

## Review Checklist for User

| Item | Reviewed |
|------|----------|
| All 8 domain models accurate | ☐ |
| Cross-domain map complete | ☐ |
| Service preview acceptable as direction | ☐ |
| Open questions acknowledged | ☐ |
| Ready for sign-off gate | ☐ |

---

## Cursor Statement

**Cursor did not decide the next roadmap step.**
