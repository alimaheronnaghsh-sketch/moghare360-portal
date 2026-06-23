# PHASE 15 SECURITY EXCLUSIONS

## Excluded From All Packages

- private/
- config.php, config.example.php
- private/erp-config.php, private/erp-config.example.php
- credentials / secrets
- real customer data
- logs/, backups/
- uploads/ with real files
- .git/
- *.bak (any filename containing `.bak`, not only `*.bak` extension)
- *.log, *.tmp

## ZIP Content Inspection

After packaging, scripts run `tar -tf` verification. The Phase 15 test tool inspects ZIP entry names for forbidden paths and extensions before PASS.

## Warning

No real backup generated in packaging phase.
