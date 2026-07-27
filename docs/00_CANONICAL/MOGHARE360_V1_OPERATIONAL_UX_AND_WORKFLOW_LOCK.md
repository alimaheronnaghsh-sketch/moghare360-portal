# MOGHARE360 V1 Operational UX and Workflow Lock

**Status:** Locked for V1 soft-run consolidation  
**Scope:** Owner UAT path from intake to delivery  
**Runtime family:** Persian RTL luxury dark green

## 1. UX Lock

- Persian RTL is the default for every product-facing route.
- Luxury Dark Green is the V1 default UX family.
- Day / Light Green is deferred as a future design-token family and is not part of this soft-run.
- Owner, staff, admin, reception and customer approval pages must use the same luxury shell family.
- Blue soft-run, raw Bootstrap, fallback white admin pages and inline raw UI are not acceptable on owner UAT routes.
- Mojibake is not acceptable in visible UI.
- Visible Persian labels must be readable; internal technical codes may remain English when they are workflow or role identifiers.

## 2. Walk-In Intake Lock

- Staff-assisted walk-in intake uses the same field model as the online customer request where practical.
- Walk-in create does not require pre-create mobile OTP.
- A valid mobile number is mandatory for walk-in create.
- Canonical customer phone binding is required for customer legal and financial gates.
- Customer legal and financial gates remain customer-facing and OTP/session/audit protected.

## 3. Hall Workflow Lock

- After reception and contract readiness, the case enters the Hall Manager Cartable.
- Hall Manager assigns team and technician.
- Technician cannot self-approve operational, financial or customer-facing requests.

## 4. Technician Request Center Lock

Supported request types:

1. Technical Additional Work Request
2. Parts / Materials Requisition
3. External Service Request
4. Customer Clarification Request
5. Work Hold / Safety Stop Request

## 5. Additional Finding Cycle Lock

- Technician raises a request in the ERP.
- Hall Manager reviews the request.
- Estimate revision or downstream action is created when cost, time, scope or risk changes.
- Customer approval is required whenever cost, time, scope or risk changes.
- The cycle can repeat up to five times.
- After five cycles, senior owner or manager override is required.

## 6. No Verbal Workflow Lock

The following are not operational proof:

- "I told Hassan"
- WhatsApp
- phone call
- verbal instruction

Only ERP-registered tasks, approvals and audit events are valid operational proof.
