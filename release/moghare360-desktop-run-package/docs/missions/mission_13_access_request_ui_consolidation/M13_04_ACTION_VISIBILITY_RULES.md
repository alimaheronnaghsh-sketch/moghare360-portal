# Action Visibility Rules

## Visible in Mission 13
- Read actions via guard evaluation display
- Access request list
- Selected request detail
- Request items table
- Approval result table
- Workflow timeline table
- View/detail links using GET request_id
- Fixed workflow viewer link for request_id 4 when file exists

## Hidden or Not Implemented
- Submit button
- Review button
- Approve button
- Apply button
- Any POST form
- Any workflow transition link
- Any Real Assignment action

## No Form
Mission 13 page must not contain:
- form element
- submit input
- POST handling

## No POST
GET request_id is allowed for read-only detail selection only.

## No Write
Mission 13 performs SELECT only.

Forbidden:
- INSERT
- UPDATE
- DELETE
- MERGE
- workflow state change
- core_user_roles write
- item_decision update

## No Real Assignment
Even when request_state = APPLIED, Mission 13 must display Real Assignment = NOT PERFORMED.

## Future Action Buttons
Future submit/review/approve/apply buttons require all of:

- Auth Context
- Permission Guard require()
- CSRF protection
- Audit strategy
- separate mission approval

## Mission 13 Decision
Read/list/view visibility only.
Action execution remains deferred.
