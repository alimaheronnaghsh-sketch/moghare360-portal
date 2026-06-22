# Role-Based Menu Visibility Rules

## Demo Role Modes (Placeholder Only)
No real permission assignment. No permission guard changes.

## Module Visibility Matrix

| Module | owner | service | reception | finance | qc |
|--------|:-----:|:-------:|:---------:|:-------:|:--:|
| Dashboard | ✓ | ✓ | ✓ | ✓ | ✓ |
| Customers | ✓ | | ✓ | | |
| Vehicles | ✓ | ✓ | ✓ | | |
| JobCards | ✓ | ✓ | ✓ | | ✓ |
| Service Operations | ✓ | ✓ | | | |
| Parts / Inventory | ✓ | ✓ | | | |
| Purchase Requests | ✓ | ✓ | | | |
| Payments | ✓ | | ✓ | ✓ | |
| QC | ✓ | | | | ✓ |
| Delivery | ✓ | | ✓ | | ✓ |
| Soft Run Gate | ✓ | ✓ | ✓ | ✓ | ✓ |

## Implementation
`moghare360_get_shell_menu($roleMode)` filters by `roles` array per module.

## Querystring
`?role=owner|service|reception|finance|qc`

## Future (M33+)
Map to real permission keys — not in M32

## Final Visibility Decision
Static PHP array filter for demo roles only.
