# PHASE 6 — VIP Detection

| Condition | Status |
|-----------|--------|
| total_score >= 80 | VIP |
| total_score >= 50 | LOYAL |
| complaint_penalty >= 30 or satisfaction < 30 | AT_RISK |
| complaint + penalty/satisfaction thresholds | COMPLAINT_PRIORITY |
| default | STANDARD |

Displayed on `erp-customer-score-board.php`.
