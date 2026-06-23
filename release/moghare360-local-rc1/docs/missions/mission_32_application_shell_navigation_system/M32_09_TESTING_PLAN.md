# Testing Plan

## Steps
1. Open `erp-app-shell-demo.php` via local XAMPP
2. Test `?role=owner` — all modules visible in sidebar
3. Test `?role=service`, `reception`, `finance`, `qc` — menu items change
4. Test sidebar toggle (desktop collapse)
5. Resize to mobile — sidebar overlay open/close
6. Verify KPI cards, workflow cards, module cards, JobCard status, Soft Run status
7. Confirm no PHP errors, no database connection
8. Run `php -l` on shell include and demo page

## Pass Criteria
- Shell renders with M31 styling
- Role switcher works
- Sidebar/topbar responsive
- No forbidden files modified

## Signoff
Update M32_90 and M32_99 after user confirms.
