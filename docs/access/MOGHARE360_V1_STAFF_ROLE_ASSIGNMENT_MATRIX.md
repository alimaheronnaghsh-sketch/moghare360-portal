# MOGHARE360 V1 — Staff Role Assignment Matrix

UI role codes map to seeded `core_roles` only — **no new roles created by P11.4**.

| UI role_code | erp_company_users.role_code | core_roles.role_key | Typical use |
|--------------|----------------------------|---------------------|-------------|
| OWNER | OWNER | owner | Company owner oversight |
| SYSTEM_ADMIN | SYSTEM_ADMIN | system_admin | Technical admin |
| RECEPTION | RECEPTION | reception_staff | Front desk |
| SERVICE_MANAGER | SERVICE_MANAGER | operations_manager | Service / ops manager |
| TECHNICIAN | TECHNICIAN | mechanical_staff | Workshop |
| PARTS | PARTS | inventory_staff | Parts / stock |
| FINANCE | FINANCE | finance_staff | Finance views |
| QC | QC | technical_manager | QC sign-off |

Protected roles (`owner`, `system_admin`) require platform system owner actor.

Permissions resolve via existing `core_role_permissions` — UI does not edit seeds.
