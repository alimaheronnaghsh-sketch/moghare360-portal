# MOGHARE360 Owner Demo Script

**Duration:** ~25–35 minutes  
**Audience:** Workshop owner / decision maker  
**Language:** Persian (RTL UI)

## Before the room

1. Log in as staff (`staff-login.php`).
2. Open `erp-soft-run-control-center.php` — confirm readiness score ≥ 70% and demo JobCard id visible.
3. Have demo JobCard number ready (prefix **`M360-DEMO`**).

## Opening (2 min)

> «امروز کل مسیر MOGHARE360 از درخواست آنلاین تا داشبورد مدیریت را روی یک JobCard نمایشی مرور می‌کنیم. این لایه Soft Run فقط وضعیت را نشان می‌دهد و workflow عملیاتی را از این صفحات تغییر نمی‌دهد.»

Show control center cards: readiness %, PASS/WARNING/BLOCKED.

## Act 1 — Intake & contract (5 min)

1. Open `erp-end-to-end-demo-scenario.php`.
2. Point to P1 online request and P1.5 contract rows — explain gate evidence columns.
3. Optionally open linked operational pages from «صفحه» links.
4. If contract is PASS, note signed contract gate; if WARNING/BLOCKED, explain what operational step is missing (do not bypass).

## Act 2 — Workshop workflow P2–P5 (8 min)

1. Scroll scenario table: reception → technical → estimate → approvals → gates → work execution.
2. Open `erp-demo-flow-map.php` for visual phase map.
3. Highlight parts/finance gates on P4 — «بدون عبور گیت، مرحله بعد باز نمی‌شود».
4. Show work execution and technical completion evidence.

## Act 3 — QC & delivery P6–P7 (8 min)

1. QC and delivery readiness stages — PASS means delivery-ready path.
2. Final invoice, settlement, customer delivery OTP/signature, vehicle release, JobCard closed.
3. Open timeline link for closed stage if JobCard is CLOSED.

## Act 4 — Management visibility P8 (5 min)

1. Click nav **داشبورد P8** or phase P8 link → `erp-management-dashboard.php`.
2. Show KPI cards and demo JobCard in pipeline if present.
3. Optional: `erp-owner-control-center.php`, `erp-bottleneck-monitor.php`, `erp-financial-control-summary.php` — all read-only.

## Closing — Readiness (3 min)

1. Open `erp-demo-readiness-report.php`.
2. Present readiness score and recommendation:
   - **Ready for internal demo** — score ≥ 90%, no BLOCKED
   - **Ready for owner soft run** — no BLOCKED, ≤ 3 WARNING
   - **Blocked** — fix migration/data/test gaps first
3. Offer checklist walkthrough in `erp-soft-run-checklist.php` for follow-up actions.

## Do not during demo

- Do not share staff credentials or config secrets.
- Do not use P9 pages to approve payments, override gates, or close JobCards.
- Do not present WARNING items as production-ready without noting gaps.

## After demo

- Export mental note of BLOCKED/WARNING stages from scenario page.
- Operator completes `MOGHARE360_SOFT_RUN_OPERATOR_CHECKLIST.md`.
- Re-run P9 test suites before next demo.
