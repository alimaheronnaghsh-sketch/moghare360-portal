# RTL Typography Rules

## Direction (Locked)
- `direction: rtl`
- `text-align: right` for body and tables

## Font Stack (Locked)
```
Vazirmatn, IRANSans, Segoe UI, Tahoma, Arial, sans-serif
```

## Numerals
- Class `m360-num` — tabular-nums, LTR embed for readability
- KPI values, table number columns, badges with IDs

## Mixed Persian / English
- Class `m360-ltr` — technical tokens (JobCard numbers, plate, status enums)
- Class `m360-input-ltr` — numeric/English form fields

## Tables
- Default cell alignment: right
- Action column: center

## Forms
- Labels: right-aligned
- Persian inputs: right-aligned
- Email/URL/technical IDs: LTR input direction

## Timeline
- Border on inline-end (right in RTL) via rtl.css overrides

## Demo
`moghare360-demo.html` uses `lang="fa" dir="rtl"`

## Final Typography Decision
Persian-first RTL with explicit LTR islands for technical data.
