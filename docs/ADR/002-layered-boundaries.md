# ADR-002: Layered OOP Dependency Boundaries

- **Status:** Accepted (selected decision; parent architecture approval gate pending)
- **Date:** 2026-08-24
- **Decision owner:** Parent/orchestrator

## Context
Rental correctness and clean-code expectations require business rules to remain independent of framework and persistence details. The selected modular monolith needs enforceable internal boundaries.

## Options considered
- Layered OOP direction: framework-free Domain; Application use cases and ports; Infrastructure adapters; Presentation delivery boundary.
- Framework-first feature code: simpler initially, but risks controllers and ORM models owning business rules.
- Separate services: stronger process isolation, but disproportionate operational and consistency complexity for this scope.

## Decision
Dependencies point inward: Domain is framework-free; Application owns use cases, ports, and transaction orchestration; Infrastructure provides Laravel/Eloquent, PostgreSQL, clock, and auth adapters; Presentation contains controllers, form requests, and Blade/Tailwind rendering. Presentation and Infrastructure may depend on Application contracts, not the reverse.

## Consequences and verification
Rules are more testable and replaceable, at the cost of explicit mapping and ports. Verify no framework dependency in Domain, no persistence rules in Presentation, and one Application transaction boundary for rental writes. Traceability: PRD §§6–9, domain §§3–7, options §§2–3. **Gate:** accepted in this package; parent approval is still required before implementation.
