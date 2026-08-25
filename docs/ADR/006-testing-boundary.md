# ADR-006: Testing Boundary and Evidence

- **Status:** Accepted (selected decision; parent architecture approval gate pending)
- **Date:** 2026-08-24
- **Decision owner:** Parent/orchestrator

## Context
The PRD requires deterministic business rules, protected Admin workflows, no partial mutation, accessibility, and concurrency correctness. No application tests exist and no runnable application manifest/scripts are present.

## Options considered
- Layered evidence: unit tests for Domain rules; integration tests for Application, persistence, authorization, transactions, and rejection paths; browser tests for principal Admin workflows; accessibility checks at browser/UI boundary.
- Browser-only coverage: observable, but too slow and indirect for formulas, normalization, lifecycle, clock, and overlap invariants.
- Unit-only coverage: fast, but cannot prove persistence atomicity, authentication boundary, concurrency, rendered workflow, or accessibility.

## Decision
Define these boundaries without creating tests: **unit** for integer-cent pricing/rounding, normalization, WIB dates, duration, lifecycle, and overlap; **integration** for use cases, Eloquent/PostgreSQL persistence, transaction rollback/no partial mutation, authorization, archive/restore, snapshots/repricing, and concurrent per-vehicle serialization; **browser** for Admin sign-in, CRUD, filters, preview, rental flows, confirmations, errors, dashboard, and history; **accessibility** checks for keyboard, labels, focus, contrast, statuses, dialogs, responsive operation, and supported browsers. Evidence must include injected-clock date-boundary cases and concurrent conflicting-write cases.

## Consequences and verification
The boundary gives fast diagnosis plus end-to-end confidence, but requires a later test design and a configured runner. Verify coverage maps to PRD §6 acceptance, §8 quality/accessibility, §9 security, business rules 1–8, domain §§3–7, and options §8. No commands are claimed because no app scripts exist. **Gate:** accepted in this package; parent validates the boundary and explicitly approves before test creation.
