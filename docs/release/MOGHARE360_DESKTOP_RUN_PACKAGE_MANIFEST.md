# MOGHARE360 Desktop Run Package Manifest

## Goal
Local Windows execution package — sanitized public_html, SQL scripts, tools, launchers.

## Included
- public_html/ (no config.php, no private)
- docs/
- sql/
- tools/ (test scripts)
- START_MOGHARE360.bat / .ps1
- CHECK_REQUIREMENTS.ps1
- INSTALL_LOCAL_COPY.ps1
- README_RUN_FIRST_FA.md

## Excluded
- private/, uploads/, logs/, backups/, .git/
- credentials, real customer data, DB backups

## Prerequisites
XAMPP, PHP, SQL Server, ODBC, manual erp-config on target machine.
