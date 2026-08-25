# Architecture Record

**Status:** Decision package for review
**Date:** 24 August 2026 (WIB)
**Validation owner:** Parent/orchestrator
**Gate:** **HOLD** pending explicit parent architecture review and approval

This record and ADR-001 through ADR-006 are an accepted/approved decision package for parent review. The parent’s explicit selection of **Laravel + PostgreSQL + Blade/Tailwind modular monolith** is the decision input recorded here; producing this package does not constitute implementation authorization. The mandatory parent review gate remains HOLD until the parent explicitly approves this architecture package. `PRD` remains the authoritative assessment and business-rule source, and [`PRD.md`](PRD.md) is the approved product baseline.

## Selected bounded architecture

Use one Laravel application and one PostgreSQL persistence boundary, organized as a modular monolith with server-rendered Blade and Tailwind UI. The module seams are **Auth**, **Vehicles/Brand-Type**, **Customers**, **Rentals**, **Dashboard**, and **Lifecycle History**. These are responsibility boundaries, not a decided folder layout, schema, migration, route set, controller map, or deployment topology.

### Layer and dependency direction

- **Domain:** framework-free OOP rules, value concepts, invariants, pricing, dates, lifecycle, and overlap semantics.
- **Application:** use cases, ports, authorization-facing contracts, and transaction boundary orchestration. It depends inward on Domain and abstractions, not on Laravel or Eloquent.
- **Infrastructure:** Laravel/Eloquent and PostgreSQL adapters, persistence, locking/concurrency mechanisms, clock, and authentication adapters.
- **Presentation:** Laravel boundary concerns such as controllers, form requests, and Blade/Tailwind rendering. It invokes Application use cases and does not own domain rules.

## Rental consistency and concurrency

A rental create, edit, archive/restore, cancel, or lifecycle write is one transaction. Validate again at save time, serialize competing writes per vehicle by locking the vehicle row before rechecking the exact inclusive overlap predicate, and commit all related state, effective-end, history, and price-snapshot changes atomically. A failed operation must produce no partial mutation. PostgreSQL range/exclusion capabilities may be evaluated as defense-in-depth alongside application/domain validation; this record does not prescribe exact DDL. An availability preview is advisory, never authoritative. Use an injected WIB clock for current-date behavior. Store integer USD cents and immutable rental pricing snapshots.

## Cross-cutting obligations

Authentication is restricted to the seeded internal Admin; there is no public account or public API surface. Customer data and credentials must be protected and minimized in logs. Blade/Tailwind delivery must preserve keyboard operation, labels, focus, contrast, meaningful statuses, understandable Indonesian errors, accessible confirmations, and responsive modern-browser operation. Testing boundaries and evidence are defined in [ADR-006](ADR/006-testing-boundary.md). Operations must later cover credential handling, backups/recovery, deployment, monitoring, and privacy-aware error/log handling; exact service targets and retention policies remain deferred.

## Consequences and alternatives

The selected shape keeps the single internal workflow cohesive, reduces operational moving parts, and gives rental writes one clear transaction coordinator. It requires disciplined module boundaries, explicit concurrency evidence, and deliberate UI accessibility work. A server-rendered modular-monolith alternative (for example Django or Node/Nest templates) and an API + SPA remain the rejected alternatives for this decision: they can fit the requirements, but add framework-selection ambiguity or client/API, authentication, deployment, and testing complexity for this release. The decision does not resolve deferred product questions.

## Traceability and unresolved questions

The package traces to `PRD` lines 13–22 and 42–47; `docs/PRD.md` FR-AUTH-01–02, FR-VEH-01–06, FR-MASTER-01, FR-CUST-01–05, FR-RENT-01–09, FR-DASH-01, business rules 1–8, and §§7–10; [`docs/domain/fleet-rental-domain-semantics.md`](domain/fleet-rental-domain-semantics.md) §§1–10; and [`docs/architecture/architecture-options.md`](architecture/architecture-options.md) §§2–11. Open PRD questions remain: lifecycle-history fields and retention, booked-cancellation-before-start reuse, exact email/phone canonical forms, pagination/browser/service targets, backup/recovery policy, and exact `today rental total` inclusion details.

No schema, exact DDL, routes, APIs, folder layout, commands, manifests, or deployment topology is decided here. The repository has no application manifest or runnable application scripts; therefore this package records no application commands.

## Handoff record

- **Phase / owner:** 3–4 — Architecture and ADR decision owner
- **Inputs:** `AGENTS.md`, `PRD`, `docs/PRD.md`, `docs/DEVELOPMENT-WORKFLOW.md`, domain semantics, architecture options, Prompt 03, existing architecture/ADR/template docs, README, and repository manifest inspection.
- **Outputs:** `docs/ARCHITECTURE.md` and six numbered ADRs under `docs/ADR/`.
- **Evidence:** Decision, alternatives, dependency direction, rental transaction/concurrency approach, cross-cutting obligations, traceability, and deferred questions are recorded above and in ADR-001–006.
- **Changed paths:** `docs/ARCHITECTURE.md`, `docs/ADR/001-modular-monolith.md`, `docs/ADR/002-layered-boundaries.md`, `docs/ADR/003-postgresql-concurrency.md`, `docs/ADR/004-blade-tailwind-ui.md`, `docs/ADR/005-admin-authentication.md`, `docs/ADR/006-testing-boundary.md`, `docs/README.md`.
- **Gate decision:** **HOLD** — parent must explicitly review and approve this architecture and each ADR. No implementation, Prompt 04, plan, schema, route, migration, or test work is authorized by this package.
- **Approver / timestamp:** Parent/orchestrator / pending; package date 24 August 2026 (WIB).
