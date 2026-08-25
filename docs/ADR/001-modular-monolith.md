# ADR-001: Laravel Modular Monolith

- **Status:** Accepted (selected decision; parent architecture approval gate pending)
- **Date:** 2026-08-24
- **Decision owner:** Parent/orchestrator

## Context
The internal release has one Admin actor and cohesive vehicle, customer, rental, dashboard, and history workflows. The parent selected Laravel as part of the Laravel + PostgreSQL + Blade/Tailwind modular-monolith decision input.

## Options considered
- Modular monolith: one deployable application with explicit module seams and a cohesive rental write boundary.
- Server-rendered modular-monolith alternative such as Django or Node/Nest templates: viable, but would require another framework selection.
- API + SPA: viable, but adds client/API, authentication, deployment, and test coordination for an internal single-role release.

## Decision
Use a Laravel modular monolith with seams Auth, Vehicles/Brand-Type, Customers, Rentals, Dashboard, and Lifecycle History. Seams are conceptual boundaries; no folder layout, routes, schema, or deployment topology is decided.

## Consequences and verification
This reduces operational moving parts and supports cohesive transactions, but requires boundary discipline and later evidence that modules do not become coupled. Verify module ownership and rental flow against PRD FR-VEH, FR-CUST, FR-RENT, and FR-DASH and options analysis §§2, 4–7. **Gate:** accepted in this package; implementation remains HOLD pending explicit parent review/approval.
