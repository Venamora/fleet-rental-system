# ADR-005: Seeded Admin Authentication Boundary

- **Status:** Accepted (selected decision; parent architecture approval gate pending)
- **Date:** 2026-08-24
- **Decision owner:** Parent/orchestrator

## Context
The product has one authenticated internal Admin and explicitly excludes public registration, customer login, password reset, and public API access. Customer data and the seeded credential are sensitive.

## Options considered
- Laravel application authentication boundary for the seeded Admin: proportionate to the internal release.
- Public registration or customer-facing accounts: out of scope and expands security/identity responsibilities.
- External identity provider or multiple roles: deferred/out of scope for the single-environment assessment.

## Decision
Protect every application operation behind the single Admin authentication boundary and keep the seeded credential out of UI, logs, documentation, and client-visible output. Authorization and validation apply to all state-changing actions. No credential format, route, provider, or schema is decided here.

## Consequences and verification
The narrow boundary reduces scope but leaves credential lifecycle, multi-role authorization, and external identity deferred. Verify unauthenticated rejection, protected state changes, safe credential handling, personal-data minimization, and absence of public account/API paths against PRD FR-AUTH-01–02 and §9; options §8. **Gate:** accepted in this package; parent approval remains required.
