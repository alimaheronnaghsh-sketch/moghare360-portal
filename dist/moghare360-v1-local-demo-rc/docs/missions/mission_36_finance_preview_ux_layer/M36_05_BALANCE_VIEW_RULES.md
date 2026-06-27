# Balance View Rules

## Components
- estimated_total (placeholder formula)
- total_received (SUM RECEIVED payments)
- balance_preview (estimated - received)

## Placeholder Formula
base 200000 + (service_count × 500000) + (part_count × 150000)

## When No Estimate
If no service ops and no parts, balance section hidden.

## Labeling
All estimate fields marked as placeholder/informational.

## Final Balance Decision
Preview math for UX only — not billing authority.
