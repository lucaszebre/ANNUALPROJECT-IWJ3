# ADR 0010: Authorization and tenant boundaries

- Status: Accepted
- Date: 2026-11-20

## Decision

The organisation selected in the session is the tenant context for a request. The
`organization.selected` middleware verifies that the authenticated user is still
a member, resolves the `Organization`, and exposes it as the `current_organization`
request attribute.

Policies must check both the current organisation and the user's membership in
that organisation. A teacher may manage only courses whose `owner_id` is their
user id. An administrator may manage every course in the current organisation.
Roles from another organisation never grant access to the selected organisation.

## 403 versus 404

Use `404 Not Found` when a resource lookup is scoped to the current organisation
and therefore must not reveal that a foreign resource exists. Use `403 Forbidden`
after a resource has been deliberately loaded in the current organisation and
the user lacks the required role or ownership permission. Controllers should
prefer scoped queries first, then call `authorize()` for the operation.
