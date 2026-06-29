# MOGHARE360 P11.7 — One-Day Run Workbench Coverage

**Phase:** P11.7 — Employee Workbench Consolidation  
**Date:** 2026-06-26

Maps each One-Day Run operational step to staff workbench cards and existing pages.

| Step | Role | Workbench group | Workbench card | Existing page | Action possible? | Remaining gap |
|------|------|-----------------|----------------|---------------|------------------|---------------|
| 1. Customer request | Customer | — | — | `customer-request.php` | Yes (public form + API) | Online bridge for remote domain |
| 2. Reception receives | RECEPTION | کار امروز | درخواست‌های آنلاین | `erp-reception-online-requests.php` | Yes (accept via detail POST) | Accept not direct card — use detail flow |
| 3. Reception JobCard | RECEPTION | کار امروز | JobCardهای پذیرش | `erp-reception-jobcards.php` → detail | Yes | Detail via list (info card on workbench) |
| 4. Contract/signature gate | RECEPTION | کار امروز | برد قراردادهای پذیرش | `erp-intake-contracts.php` | Yes | Customer sign pages separate |
| 5. Service manager assigns | SERVICE_MANAGER | پیگیری | جزئیات فنی JobCard | `erp-technical-jobcard-detail.php` | Yes (`assign_technician`) | From technical board, not direct link |
| 6. Technician diagnosis | TECHNICIAN | کار امروز | تابلوی فنی | `erp-technical-board.php` → detail | Yes | No “my jobs” filter yet (backlog card) |
| 7. Parts reserve/use | PARTS | کار امروز | رزرو / مصرف قطعه | `erp-part-reserve.php`, `erp-jobcard-part-use.php` | Yes | Purchase list page missing (backlog) |
| 8. Estimate/payment/invoice | FINANCE | کار امروز | برآورد / پرداخت / فاکتور | `erp-estimate-board.php`, `erp-payment-tracking.php`, `erp-final-invoice-board.php` | Yes | Finance center hub missing (backlog) |
| 9. Work execution | TECHNICIAN | کار امروز | تابلوی اجرای کار | `erp-work-execution-board.php` → detail | Yes | — |
| 10. QC | QC | کار امروز | تابلوی QC | `erp-qc-board.php` → detail | Yes | Detail via board (info card) |
| 11. Settlement | FINANCE | پیگیری | جزئیات تسویه | `erp-settlement-detail.php` | Yes | From invoice board flow |
| 12. Delivery/close | QC / Customer | کار امروز / customer | کنترل تحویل | `erp-delivery-control.php`, customer delivery pages | Partial | Customer OTP delivery separate path |

## Workbench entry by role (P11.7)

| Role | Start here (کار امروز) |
|------|-------------------------|
| RECEPTION | درخواست‌های آنلاین → JobCardهای پذیرش → برد قراردادها |
| SERVICE_MANAGER | تابلوی فنی → تابلوی اجرا → تابلوی QC |
| TECHNICIAN | تابلوی فنی → تابلوی اجرای کار |
| PARTS | رزرو قطعه → مصرف قطعه → درخواست خرید |
| FINANCE | پیگیری پرداخت → برد برآورد → فاکتور نهایی |
| QC | تابلوی QC → کنترل تحویل |
| OWNER | مدیریت دسترسی → خانه محصول |

## Backlog items (not blocking navigation)

- Technician assigned-jobs filter
- `erp-finance-center.php`
- `erp-jobcard-part-usage-list.php` (legacy name)
- `erp-purchase-request-list.php`
- HR self-service (P15)

P11.7 consolidates existing pages into role workbenches without new workflow logic.
