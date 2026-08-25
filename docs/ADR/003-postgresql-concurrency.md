# ADR-003: PostgreSQL Rental Concurrency

- **Status:** Accepted (selected decision; parent architecture approval gate pending)
- **Date:** 2026-08-24
- **Decision owner:** Parent/orchestrator

## Context
Inclusive boundary-touching overlaps must be rejected even when concurrent Admin writes target one vehicle. Preview cannot be authoritative, and rejected writes must not partially mutate records.

## Options considered
- PostgreSQL transaction with per-vehicle row serialization before the overlap recheck: directly coordinates the application invariant.
- PostgreSQL range/exclusion constraint alone: useful defense-in-depth, but cannot alone express all blocking-state and cancellation semantics; exact DDL is deferred.
- Unserialized application checks: simple, but permits a race in which conflicting writes both succeed.

## Decision
Use one transaction for each rental state-changing write. Lock/serialize on the vehicle row, then recheck the exact inclusive predicate and blocking states (excluding the edited rental), and atomically apply state, effective-end, lifecycle history, and integer-cent price snapshots. PostgreSQL range/exclusion support may be added as defense-in-depth, not as a prescribed exact DDL design. Use an injected WIB clock.

## Consequences and verification
This protects correctness but may serialize writes per vehicle and requires deadlock/rollback evidence. Verify concurrent conflicting attempts, boundary dates, edit self-exclusion, no partial mutation, cancellation semantics, snapshots, and clock boundaries against PRD business rules 1–8 and domain §§3–7; options §3. **Gate:** accepted in this package; parent review/approval remains mandatory.
