# Implementation Boundary

## Allowed in Mission 31
- New files under `public_html/assets/moghare360-ui/`
- New docs under `docs/missions/mission_31_erp_design_system_foundation/`
- Static HTML demo

## Forbidden in Mission 31
- config.php, config.example.php
- staff-auth.php, access-control.php
- Customer Portal / legacy portal PHP
- Any existing ERP PHP page modification
- SQL scripts
- Auth / login / permission changes
- Core database schema
- Production deploy
- Write logic changes

## Integration Path (Future — M32+)
1. Link CSS files from ERP PHP pages
2. Replace inline styles gradually
3. Preserve existing write/auth logic unchanged

## Demo Constraints
- moghare360-demo.html: no PHP, no database, no auth

## Final Boundary Decision
Mission 31 adds assets only; zero impact on operational PHP/SQL/auth.
