# ADR-004: Blade and Tailwind Server-Rendered UI

- **Status:** Accepted (selected decision; parent architecture approval gate pending)
- **Date:** 2026-08-24
- **Decision owner:** Parent/orchestrator

## Context
The release is an internal Admin workflow centered on forms, tables, filters, confirmations, and date-aware rental feedback. The parent selected Blade/Tailwind with Laravel.

## Options considered
- Blade/Tailwind server-rendered UI: fits the workflow and provides a straightforward HTML accessibility baseline.
- API + SPA: richer client interaction, but adds client state, API error, authentication, and accessibility complexity.
- Other server templates: viable, but inconsistent with the selected Laravel decision input.

## Decision
Use server-rendered Blade views with Tailwind styling, while keeping authoritative validation and business rules in Application/Domain. Preview remains advisory; save repeats validation. No view, style, route, or component structure is decided by this ADR.

## Consequences and verification
The approach limits deployment complexity but needs deliberate preview feedback and progressive enhancement where useful. Verify keyboard operation, labels, focus, contrast, statuses, Indonesian errors, accessible confirmations, responsive behavior, and modern-browser workflows against PRD §§6–8 and options §8. **Gate:** accepted in this package; parent architecture approval remains pending.
