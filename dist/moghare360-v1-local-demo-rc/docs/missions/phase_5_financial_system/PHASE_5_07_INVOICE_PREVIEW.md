# PHASE 5 — Invoice Preview

Page: `public_html/erp-invoice-preview.php`

## Features

- GET `cost_header_id` required (or search form)
- Displays customer, operation case, cost lines, totals, payment status
- Controlled create internal preview: `INV-PREV-YYYYMMDD-HHMMSS-random4`
- UI warning: **این پیش‌نمایش داخلی است و فاکتور رسمی مالیاتی نیست.**

## Explicitly Not Built

- Official invoice number
- Tax / VAT calculation
- Accounting document export

CSRF: `finance_invoice_preview`
