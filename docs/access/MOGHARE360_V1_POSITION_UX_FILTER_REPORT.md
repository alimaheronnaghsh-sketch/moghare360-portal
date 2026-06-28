# MOGHARE360 V1 — P11.4.2 Position UX Filter Report

## Summary

P11.4.2 fixes the Access Management **position dropdown UX** only. Staff create/edit no longer show all 43 positions in a single flat list. Positions are filtered by the selected department using embedded UTF-8 JSON and client-side JavaScript, with server-side department–position validation on submit.

**No seed data changed.** **No Auth/Login or permission architecture changed.** **No database schema changes.**

## Problem (P11.4.2-0 Discovery)

- `core_departments`: 14 departments
- `core_positions`: 43 positions
- Create Staff loaded all 43 positions in one dropdown
- Duplicate generic Persian labels (e.g. مدیر واحد) appeared many times
- Edit Staff filtered by saved department but did not reload positions when department changed

## Solution

### Create Staff (`erp-access-user-create.php`)

- Department select appears first
- Position select is **disabled** until a department is selected
- Embedded JSON map: `department_id => positions[]` via `m360_access_user_positions_json_for_form()`
- Client script: `assets/js/m360-access-position-filter.js`
- Help text: «ابتدا واحد را انتخاب کنید تا سمت‌های همان واحد نمایش داده شود.»
- Routine create hides `executive_management` department positions (`M360_ACCESS_ROUTINE_HIDDEN_DEPT_KEYS`)

### Edit Staff (`erp-access-user-edit.php`)

- Loads existing department and position
- Same JSON + JS; positions reload when department changes
- All departments shown (including executive) so existing assignments remain editable

### Server-side validation

`m360_access_user_require_department_position_pair()` enforces:

- Active `department_id` when provided
- Active `position_id` when provided
- `position.department_id` must match selected `department_id`

Mismatch error (Persian): **سمت انتخاب‌شده با واحد انتخاب‌شده همخوانی ندارد.**

Used by `m360_access_user_create()` and `m360_access_user_update()`.

### Helpers (`m360-access-user-helper.php`)

- `m360_access_user_departments_for_staff_form()`
- `m360_access_user_positions_by_department_map()`
- `m360_access_user_dept_label_map()`
- `m360_access_user_positions_json_for_form()`
- `m360_access_user_dept_labels_json_for_form()`
- `m360_access_user_render_department_position_fields()`

Reuses existing `m360_access_user_departments()` and `m360_access_user_positions($conn, $departmentId)`.

## Out of scope (this phase)

- Position seed cleanup → **P11.4.3** (owner approval)
- Auto-assign `role_code` from position
- New roles, permissions, or Auth/Login changes
- P12 taxonomy, accounting/payment scope

## Tests

- `tools/test-p11-4-2-position-dependent-dropdown.php`
- `tools/test-p11-4-2-position-validation.php`
- `tools/test-p11-4-2-scope-security.php`
