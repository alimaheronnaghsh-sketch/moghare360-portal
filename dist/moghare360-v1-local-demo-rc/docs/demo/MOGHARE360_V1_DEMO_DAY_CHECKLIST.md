# MOGHARE360 V1 — Demo Day Checklist

## قبل از دمو

- [ ] Staff login تست شده
- [ ] داده DEMO (M360-DEMO) آماده
- [ ] `erp-owner-presentation-lock.php` مرور شده
- [ ] Presentation script چاپ یا باز در تب جدا
- [ ] اتصال DB محلی/local برقرار
- [ ] هیچ credential واقعی در صفحه نمایش نیست

## هنگام دمو

- [ ] ترتیب ۱۰ مرحله presentation script رعایت شود
- [ ] Gateهای P1.5 تا P7 بدون skip نشان داده شوند
- [ ] KPI مدیریتی نمایش داده شود
- [ ] محدودیت‌های V1 صریح گفته شود
- [ ] قول حسابداری/درگاه/بانک/مالیات/SaaS داده نشود

## بعد از دمو

- [ ] Owner signoff checklist تکمیل شود
- [ ] بازخورد ثبت شود (bugfix list)
- [ ] RC final lock doc به مالک ارجاع داده شود

## مشکلات احتمالی

| مشکل | مسیر برگشت |
|------|------------|
| DB قطع | Soft Run Control → retry local SQL |
| صفحه 404 | Route Map → Link Audit |
| KPI خالی | داده DEMO seed / soft run scenario |
| Login fail | staff-login.php — credential محلی |

## مسیر برگشت

1. `erp-product-home.php`
2. `erp-soft-run-control-center.php`
3. `erp-rc-final-audit.php`
