# APEX 04 — Technical Intelligence Engine Position

## Domain Placement

The **Technical Intelligence Engine** is located **inside the Job & Technical Intelligence Domain**.

It is not a standalone silo and not a generic analytics plugin. It is an integral part of workshop operations — fed by JobCards, cases, symptoms, repairs, and parts usage — while respecting domain boundaries.

---

## Engine Role in Product Architecture

| Role | Description |
|------|-------------|
| Industrial memory | Preserves workshop technical knowledge across cases |
| Diagnosis support | Suggests likely causes from historical patterns |
| Technician measurement | Links outcomes to performance and skill data (via HR boundary) |
| Failure learning | Aggregates symptoms, causes, and outcomes into patterns |
| Suggestion engine | Produces ranked recommendations for technicians |

---

## MVP Engine — Inputs

The MVP Technical Intelligence Engine accepts:

| Input | Source |
|-------|--------|
| Brand | Vehicle / case context |
| Model | Vehicle / case context |
| Mileage | Vehicle / case context |
| Symptom | Classified symptom from case |
| Season | Environmental / temporal context |
| History | Prior cases and repairs for vehicle/customer |

Inputs are captured through Job & Technical Intelligence workflows. External domains (CRM, Inventory) supply reference data **via service boundaries only**.

---

## MVP Engine — Output

| Output | Format |
|--------|--------|
| Ranked probability list | Ordered list of likely root causes or repair paths with confidence scores |

Example logical output (not API contract):

1. Root Cause A — 42%
2. Root Cause B — 28%
3. Root Cause C — 15%

---

## MVP Engine — Implementation Class

**Frequency-based engine** (rule and statistics driven):

- Symptom → root cause frequency tables
- Brand/model/mileage segmentation
- Seasonal weighting where applicable
- `SuggestionRule` entities for explicit business rules

No machine learning model is required for MVP. The engine must be **explainable** — technicians and managers can see why a suggestion ranked highly.

---

## Advanced Engine — Phase 2+

| Capability | Description |
|------------|-------------|
| ML model | Trained failure prediction models |
| Cross-workshop dataset | Anonymized aggregated learning data |
| Predictive maintenance | Mileage/time-based failure anticipation |
| Technician recommendation | Skill-matched assignment suggestions |
| International exchange | Cross-border technical intelligence sharing |

Advanced engine **extends** MVP frequency logic; it does not replace operational truth in Job domain.

---

## Data Ownership Rules for Intelligence

| Rule | Requirement |
|------|-------------|
| Operational truth | Owned by Job domain (JobCard, case status, delivery) |
| Intelligence reads | May read operational snapshots via services/events |
| Intelligence writes | Owns CaseRecord, Symptom, RootCause, FailurePattern, SuggestionRule |
| No direct mutation | Must not directly mutate Inventory or Finance tables |
| Ledger isolation | Finance must not be polluted by intelligence scoring writes |

---

## Strategic Value

| Value | Impact |
|-------|--------|
| Industrial memory | Workshops retain knowledge when technicians leave |
| Technician performance | Measurable link between diagnosis quality and outcomes |
| Failure learning | Repeat failures become visible and preventable |
| Suggestion engine | Faster diagnosis, fewer comebacks |
| Network effect (Phase 2+) | Workshops benefit from collective intelligence |

---

## Cursor Statement

Cursor documented engine position only. **Cursor did not decide the next roadmap step.**
