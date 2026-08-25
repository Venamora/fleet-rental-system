# Fleet & Rental — Domain Semantics

**Status:** Draft domain artifact; implementation-neutral  
**Authority:** `PRD` is the authoritative assessment and business-rule source; approved `docs/PRD.md` v1.0 elaborates the product baseline.  
**Validation owner:** Parent/orchestrator  
**Gate:** **HOLD** pending explicit parent approval. Architecture and implementation work must not pass this gate by implication.

## 1. Glossary and actors

- **Admin:** The sole authenticated internal actor. The Admin maintains vehicles, brands/types, customers, and rentals, and may perform the permitted lifecycle actions. There is no public or customer-facing actor in this release.
- **Vehicle:** A retained rentable fleet unit identified by a unique normalized plate. It has one separate Brand, one separate Type, a daily rate in integer USD cents, and optional year and color.
- **Brand / Type:** Separate master concepts; each Type belongs to exactly one Brand, and Type names are unique within a Brand (but may repeat under another Brand).
- **Customer:** A reusable retained master record with required name, email, and Indonesian mobile phone number. A customer can be linked to many rentals and cannot be deleted or archived.
- **Rental:** One customer's reservation for one vehicle over one inclusive calendar date range, with one lifecycle state, price snapshot, and lifecycle history.
- **Date range:** A pair of calendar dates, start and end, interpreted in WIB. Both endpoints count.
- **Effective end date:** The end used for availability and lifecycle purposes. For a cancelled rental, it is the cancellation date. A cancelled booked rental nevertheless frees its whole future range and is non-blocking.
- **Lifecycle history:** The retained record of rental creation, relevant state changes, and cancellation details; it must explain cancellation reason, timestamp, and effective date without unrelated activity noise.
- **Daily-rate snapshot / price snapshot:** Values captured for a rental’s pricing at the applicable point in time. Later vehicle-rate changes do not rewrite an existing rental’s stored price snapshot.
- **Monetary amount:** An integer number of USD cents. Display formatting is USD with two decimal places; a final price is never a floating-point result.
- **WIB:** Western Indonesian Time, UTC+7, used for the current date and all calendar-day boundaries.
- **Archived vehicle:** A retained vehicle excluded from new rentals and normal active-fleet availability. Archive does not erase history; restore does not bypass date or conflict validation.

## 2. Relationships

- A Vehicle references exactly one Brand and one Type; a Brand or Type may be referenced by many Vehicles. Application validation requires the selected Type's Brand to match the Vehicle Brand; persistence intentionally does not use a composite foreign key.
- A Rental references exactly one Vehicle and exactly one Customer.
- A Vehicle and Customer retain their links to historical Rentals. Vehicles are never hard-deleted; Customers are never deleted or archived.
- A Rental has exactly one state at a time and an ordered lifecycle history. Its pricing snapshot belongs to that Rental rather than being recomputed from the current Vehicle rate for historical states.

## 3. Core invariants

### Identity and master data

1. Vehicle plate is required, trimmed and uppercased on entry and comparison; no two vehicles share the same normalized plate.
2. Vehicle Brand, Type, and daily rate are required; year and color are optional. Brand and Type remain separate concepts.
3. Customer name, email, and Indonesian mobile phone are required. Normalized email and normalized phone are each unique among customers. Editing a customer does not break rental links; customer retention has no delete/archive operation.
4. Archived vehicles cannot be selected for a new rental. Current vehicle status is derived, never independently edited.

### Dates, duration, and booking validity

5. Dates are WIB calendar dates. `end_date` must not precede `start_date`, and a new or edited rental cannot have a start date before the current WIB date.
6. Duration is inclusive: `duration_days = (end_date - start_date) + 1`. A same-day range has duration one.
7. A rental has one vehicle, one customer, one date range, one applicable daily-rate snapshot, and one total. Extensions, split rentals, add-ons, taxes, deposits, and currency conversion are outside this semantic baseline.

### Conflict and availability

8. For the same vehicle, a requested range and an existing range overlap exactly when:

   `requested_start <= existing_effective_end AND requested_end >= existing_start`

   Boundary-touching dates therefore conflict. When editing, the rental being edited is excluded from its own conflict comparison.
9. `booked` and `active` rentals block their vehicle’s dates. Completed rentals do not block dates after their effective end. A cancelled active rental blocks only through its cancellation/effective end date. A cancelled booked rental is non-blocking and frees its entire future date range.
10. Availability preview and save-time validation use the same date-aware rules. Archived status independently excludes a vehicle from new-rental selection.

### State and money

11. A Rental’s state is exactly one of `booked`, `active`, `completed`, or `cancelled`. Its state, dates, vehicle identity, customer identity, and lifecycle history cannot be changed through unauthorized or impermissible operations.
12. Daily rate, subtotal, discount, and total are integer USD cents. Before discount, `subtotal_cents = duration_days × daily_rate_cents`. For duration greater than seven days, discount is 10% and `total_cents = round_half_up(subtotal_cents × 90 / 100)`; otherwise total equals subtotal. The rounded total is stored as the rental price snapshot.
13. A future booked rental is repriced only when its vehicle or dates change, using the current applicable daily rate and complete revised range. Unrelated permitted edits do not reprice it. Active, completed, and cancelled rentals are never repriced.

## 4. Lifecycle states, effective end, and permissions

### State definitions

- **Booked:** A future confirmed reservation not yet started. It blocks its inclusive range unless cancelled.
- **Active:** Its start date has arrived and its effective end date has not passed. It blocks through its effective end date.
- **Completed:** Ended normally or automatically after its effective end date. It does not block dates after that effective end.
- **Cancelled:** Stopped by the Admin with a non-empty reason, cancellation timestamp, and effective cancellation date. The effective end is the cancellation date. A booked cancellation is explicitly non-blocking for its whole future range; an active cancellation truncates blocking at cancellation date.

### Allowed transitions and permissions

- Creation produces `booked` only when the requested start is not past and all vehicle, customer, date, overlap, and pricing invariants pass.
- A booked rental may be fully edited (vehicle, customer, dates, and other permitted details), subject to validation. Vehicle/date changes reprice it; unrelated permitted changes do not.
- A booked rental may be cancelled after explicit confirmation and a reason. Its future range is freed, including the cancellation-before-start case; whether a cancellation before start permits immediate reuse on that date remains an open PRD question where more precision is needed (see §8), not an invented answer here.
- An active rental may edit only its end date, and only while the current WIB date is on or before its end date. Vehicle, customer, and start date are immutable. The revised end remains subject to date and overlap rules and uses no historical repricing.
- An active rental may be cancelled with confirmation and reason; its effective end becomes the cancellation date and its blocking range ends there.
- A rental is automatically completed on the day after its effective end date. The Admin may manually complete it only on or after its start date; completion before start is rejected.
- Completed and cancelled rentals are not repriced and cannot be edited as booked/active rentals. Any action not explicitly permitted is rejected.

### Derived vehicle status and archive/restore

For today’s WIB date, derived status is evaluated in this order: `archived` when archived; otherwise `di-sewa` when a blocking active rental covers today; otherwise `tersedia`. Future booking availability is a separate date-range evaluation, not a reinterpretation of today’s status. Archive requires explicit confirmation, preserves all records/history, prevents new rentals, and removes the vehicle from rentable availability. Restore requires explicit confirmation and makes the vehicle eligible again only after normal date-aware conflict and validation checks.

## 5. Normalization

- Plate normalization is fixed: trim surrounding whitespace, then uppercase, for both entry and comparison.
- Email and phone must be normalized consistently before uniqueness comparison and storage/display according to accepted canonical forms; email case and whitespace normalization are required facts.
- Indonesian mobile phone input must use the accepted canonical Indonesian mobile format. The exact canonical phone representation and the complete accepted email canonical-form specification are deliberately deferred; this artifact does not invent them.
- Normalization applies consistently to duplicate checks and must not break the human-readable representation where useful or leak unnecessary customer-existence information.

## 6. Pricing semantics and examples

All amounts below are cents unless shown as USD. A one-day rental from 1–1 March at `$50.00` (`5,000`) has duration 1, subtotal 5,000, total 5,000 (`$50.00`). A 1–8 March rental at `$100.00` (`10,000`) has duration 8, subtotal 80,000, discount 8,000, and total 72,000 (`$720.00`). A qualifying subtotal of 10,001 gives 9,000.9 before cent rounding and therefore 9,001 (`$90.01`) under half-up rounding. A later vehicle-rate change cannot alter these stored snapshots.

For overlap, a requested 10–12 June range conflicts with an existing 12–15 June range because `10 <= 15` and `12 >= 12` are both true. A rental ending 20 June remains active through 20 June and automatically completes on 21 June; if cancelled on 18 June, its effective end is 18 June.

## 7. Rejection and no partial mutation

Validation is repeated at save time even when previewed earlier. Reject blank/invalid required data, invalid rates, duplicate normalized identities, reversed or past-start ranges, archived-vehicle selection, conflicts, unauthorized state changes, premature completion, and impermissible edits. Conflict rejection must identify the unavailable vehicle and sufficient date context. Cancellation requires confirmation and a non-empty reason.

Any failed create, edit, archive, restore, cancel, or lifecycle operation leaves the attempted record and all related records unchanged: no partial date, state, price, link, archive flag, or history mutation is permitted. A rejected edit leaves the prior rental intact.

## 8. Deliberate open questions (not answered here)

The following remain PRD section 10 decisions and require approved clarification if implementation needs more precision:

1. What exact lifecycle-history event fields are required beyond creation, relevant state changes, cancellation reason, cancellation timestamp, and effective date?
2. What lifecycle-history retention duration is required?
3. Does cancellation before the booked rental’s start permit immediate reuse on that cancellation/start date, or is another interpretation intended? The approved rule that a cancelled booked rental frees its whole future date range and does not block availability remains recorded, but this semantic edge needs product clarification rather than an invented answer.
4. What exact accepted canonical forms should be used for Indonesian mobile phone values and email normalization beyond the stated trimming/case consistency facts?

Pagination size, filter facets, browser matrix, and service-level targets may be selected later without changing these business rules. Architecture, storage, files, routes, APIs, and deployment topology are intentionally not domain decisions.

## 9. Dashboard metric semantics

FR-DASH-01 defines exactly five dashboard core metrics: **total active vehicles**, **currently rented**, **available today**, **upcoming bookings**, and **today rental total**. Each metric is derived from persisted records using the current WIB date together with the archive, active/blocking, and inclusive-date semantics already stated in this artifact. The PRD does not define additional inclusion details for `today rental total`; this artifact does not invent them.

## 10. Traceability

| Domain coverage | PRD traceability | Assessment coverage |
|---|---|---|
| Sole Admin, protected internal role | FR-AUTH-01–02; §1–2 | Internal fleet/rental scope, `PRD` lines 5–7, 25–29 |
| Vehicle identity, CRUD fields, search/filter, derived status, archive/restore, no hard delete | FR-VEH-01–06; FR-MASTER-01 | Vehicle fields/status/search/filter, lines 13–15; master-data quality, lines 42–47 |
| Customer identity, normalization, reuse, retention | FR-CUST-01–05; PRD §9 | Not separately named in assessment; supported by approved PRD expansion of rental domain |
| Rental creation, states, permissions, history, cancellation, completion | FR-RENT-01–09 | Vehicle + start/end rental form, lines 17–18; lifecycle/status improvement, lines 42–47 |
| Dashboard core metrics derived from persisted records and current WIB date | FR-DASH-01; PRD §4 and §6 | No direct assessment feature line; aligned with the approved product baseline |
| WIB inclusive dates, same-day duration, past start, overlap predicate and blocking states | PRD business rules 1–5; FR-RENT-03–04 | Mandatory overlap rejection and rental dates, lines 17–22 |
| Integer cents, >7-day 10% discount, half-up rounding, snapshots/repricing | PRD business rules 6–8; FR-RENT-01, FR-RENT-06 | Automatic discount over 7 days, lines 19–20; correct business logic, lines 42–46 |
| Rejection and no partial mutation | FR-RENT-04; PRD §§6–7 | Mandatory overlap rejection, lines 21–22; clean/correct business logic, lines 42–47 |
| Examples and unresolved decisions | PRD §§5, 10, 13 | Documentation/analysis expectation, lines 33–38 and 42–47 |

All eight PRD business rules are represented explicitly in §§3–7: (1) date/duration, (2) no past starts, (3) blocking/cancellation, (4) exact overlap, (5) archive, (6) cents, (7) discount/rounding, and (8) snapshot/repricing.

## 11. Phase-2 handoff record

- **Phase / owner:** 2 — Domain semantics / domain owner
- **Inputs:** `PRD`; approved `docs/PRD.md` v1.0; `docs/DEVELOPMENT-WORKFLOW.md`; `prompts/01-domain-semantics.md`; repository instructions in `AGENTS.md`
- **Output:** `docs/domain/fleet-rental-domain-semantics.md`
- **Evidence:** Those five source files were inspected; this artifact contains the glossary, relationships, invariants, lifecycle/date/overlap/money semantics, examples, rejection behavior, open questions, and FR/business-rule traceability.
- **Unresolved questions:** PRD §10 items listed in §8, including cancellation-before-start reuse clarity and canonical-form details.
- **Gate decision:** **hold** — parent/orchestrator must explicitly approve the domain semantics.
- **Approver / timestamp:** Parent/orchestrator / pending; artifact creation date 24 August 2026 (WIB).
- **Next-gate constraint:** No architecture, ADR, implementation plan, application code, schema, routes, UI, migrations, or tests are authorized by this handoff.
