# Permission and Audit Rules

## Purpose
This document defines permission and audit rules for future JobCard part usage implementation.

## Required Security Layers (Locked)
Every future part usage write must use:
- Auth Context
- Permission Guard
- CSRF (browser POST)
- Controlled transaction
- Audit / history on usage table
- Stock movement in same transaction when ISSUE applies
- Safe error handling
- **No silent stock change**
- **No direct stock balance update**

## Suggested Permission Actions (Future)

| Permission Key | Purpose |
|----------------|---------|
| jobcard.part.use | Register part usage (create USED + ISSUE) |
| jobcard.part.view | View single usage detail |
| jobcard.part.list | List usages for JobCard / shop |
| jobcard.part.reverse | Reverse or cancel usage (RETURN / REVERSAL) |
| stock.issue.create | Authorize ISSUE movement creation |
| stock.return.create | Authorize RETURN movement creation |

## Permission Mapping (Mission 24 Indicative)
| Action | Permissions |
|--------|-------------|
| Create usage + ISSUE | jobcard.part.use + stock.issue.create |
| List usages | jobcard.part.list |
| View usage | jobcard.part.view |
| Return / reverse | jobcard.part.reverse + stock.return.create |

## Platform Owner Prototype Rule
Local controlled prototypes may use placeholder permission fallback:
- user_id = 10001
- owner / system_admin context
- local prototype only

## Audit Rules (Locked)

### Usage History on Create
Minimum history row:
- action_code = PART_USAGE_CREATED (or equivalent)
- old_status = NULL
- new_status = USED

### Usage History on Return / Reversal
- old_status / new_status captured
- change_note required for reverse actions

### Stock Movement Audit
Every ISSUE / RETURN / REVERSAL linked to `part_usage_id` via reference_type = JOBCARD_PART_USAGE.

## Workflow Checks (Future — Mission 24)
1. Auth Context resolves user
2. Permission Guard allows action
3. CSRF validated on POST
4. JobCard / Service Operation / Part / Location validated
5. quantity_on_hand checked
6. Transaction: usage INSERT → history INSERT → movement INSERT
7. COMMIT or full ROLLBACK

## Forbidden Changes (Locked)
No change to:
- config.php, config.example.php, staff-auth.php, access-control.php
- Customer Portal files
- Legacy inventory files

## Mission 23 Boundary
Permissions documented only. No registration or code.

## Final Permission Decision
Six permission keys locked; dual permission for issue/return; full audit stack mandatory.
