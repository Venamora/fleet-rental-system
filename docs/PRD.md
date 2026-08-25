# Product Requirements Document — Fleet & Rental System

**Status:** Approved baseline
**Version:** 1.0
**Date:** 24 August 2026
**Validation owner:** Parent/orchestrator

## 1. Objective and problem

Provide a focused internal web application for an administrator to maintain a fleet and manage vehicle rentals. The product must make vehicle master data, customer history, date-based availability, booking conflicts, rental pricing, and lifecycle state visible and reliable. It replaces ad-hoc tracking with enforceable rules for dates, overlap, pricing, and vehicle status.

### Users

The sole user role is an authenticated internal **Admin**. The initial release runs in one internal Indonesian environment, uses WIB (UTC+7), and has one seeded admin credential. There is no public or customer-facing user role.

## 2. Scope

### In scope

- Admin sign-in using the seeded environment credential.
- Vehicle master data: separate brand and type, required plate and daily rate in USD cents, and optional year and color.
- Vehicle search, filtering, sensible pagination, archive/restore, and derived current status.
- Reusable customer master data with normalized uniqueness, editable details, and retention without delete/archive.
- Rental creation and management across booked, active, completed, and cancelled states.
- Inclusive-date availability preview and overlap protection.
- USD-cent pricing, duration-based pricing, discounting, and booking price snapshots.
- Dashboard core counts and rental/vehicle/customer history.
- Confirmation for archive, restore, and cancellation actions.
- Responsive, accessible operation in modern browsers.

### Out of scope

- Public API, public registration, customer self-service, password reset, or notifications.
- Online payments, invoices, accounting, deposits, refunds, or payment reconciliation.
- Multi-tenant or multi-environment administration, multiple admin roles, or permissions beyond the single Admin.
- GPS/telematics, maintenance/work orders, insurance, damage inspection, driver management, or inventory other than vehicles.
- Automatic customer communications, external identity providers, localization other than the Indonesian/WIB operating context, or exchange-rate conversion.
- Hard deletion of vehicles or a requirement to delete historical customers/rentals.

## 3. Vocabulary

- **Vehicle:** A rentable fleet unit identified by a unique normalized plate.
- **Brand / type:** Separate vehicle master attributes (for example, Toyota and Avanza); neither is a combined free-text field.
- **Customer:** A reusable rental customer master record.
- **Rental:** A reservation and its lifecycle record for one vehicle and one customer.
- **Booked:** Future, confirmed reservation not yet started.
- **Active:** Rental whose start date has arrived and whose effective end date has not passed.
- **Completed:** Rental ended normally or automatically after its end date.
- **Cancelled:** Rental stopped by an administrator, with a reason, cancellation timestamp, and effective cancellation date. A booked cancellation frees the rental's whole future date range; it does not block vehicle availability.
- **Effective end date:** The end used for availability and lifecycle purposes; for a cancelled rental it is the cancellation date.
- **Inclusive dates:** Both start and end dates count toward duration and conflict detection.
- **Archived vehicle:** A retained vehicle excluded from new rentals and normal active-fleet availability.
- **WIB:** Western Indonesian Time, UTC+7.

## 4. Functional requirements

### Authentication and access

**FR-AUTH-01 — Admin access.** The system shall allow only the seeded Admin credential to access the internal application.

**FR-AUTH-02 — No public account features.** The release shall not expose public registration, password reset, customer login, or public API access.

### Vehicle management

**FR-VEH-01 — Vehicle records.** The Admin shall create and edit a vehicle with brand, type, plate, daily rate in USD cents, and optional year and color. Plate, brand, type, and rate are required except year and color.

**FR-VEH-02 — Plate normalization.** On entry and comparison, the plate shall be trimmed and uppercased. Two vehicles may not share the same normalized plate.

**FR-VEH-03 — Vehicle listing.** The Admin shall view a paginated vehicle list with search and useful filters, including active/archived and derived availability status.

**FR-VEH-04 — Archive and restore.** The Admin shall archive or restore a vehicle after explicit confirmation. Archiving preserves its records and history, prevents new rentals, and removes it from rentable availability. Restoring makes it eligible again subject to date-aware conflict rules.

**FR-VEH-05 — No hard delete.** Vehicles shall never be hard-deleted. Existing rentals and history remain attributable to the vehicle.

**FR-VEH-06 — Derived current status.** Current vehicle status shall be derived, not independently editable: `archived` when archived; `di-sewa` when an active rental covers today; and `tersedia` otherwise. Future booking evaluation is separate and shall be provided by the date-aware preview.

### Brand and type

**FR-MASTER-01 — Separate master values.** Brand and type shall be maintained and selected as separate values, with no requirement to manage them as one combined label. The Admin shall be able to use them consistently in vehicle entry and filtering.

### Customer management

**FR-CUST-01 — Customer master.** The Admin shall create a reusable customer with required name, email, and Indonesian mobile phone number.

**FR-CUST-02 — Customer edit.** The Admin shall edit customer details without breaking links to existing rentals.

**FR-CUST-03 — Customer normalization and uniqueness.** Email and phone shall be normalized before comparison and storage/display according to the product’s accepted canonical forms. Normalized email and normalized Indonesian mobile phone must each be unique among customers.

**FR-CUST-04 — Customer reuse.** Rental entry shall select an existing customer master rather than requiring a new duplicate record for every rental.

**FR-CUST-05 — Customer retention.** Customer records shall not be deleted or archived; customer history remains available and linked to rentals.

### Rental lifecycle and entry

**FR-RENT-01 — Rental creation.** The Admin shall create a rental by selecting a vehicle and customer and entering a start and end date.

**FR-RENT-02 — States.** A rental shall have exactly one lifecycle state: booked, active, completed, or cancelled, with state changes governed by the rules in section 5.

**FR-RENT-03 — Date-aware preview.** Before saving a rental, the Admin shall see which vehicles are available for the requested inclusive date range and why otherwise eligible vehicles are unavailable.

**FR-RENT-04 — Overlap rejection.** The system shall reject a create or edit that conflicts with another blocking rental for the same vehicle, and shall leave the attempted conflicting edit unchanged.

**FR-RENT-05 — Rental list and history.** The Admin shall view rentals with sensible pagination and filters for state, vehicle, customer, and date. Rental history shall retain lifecycle events only (creation, relevant state changes, and cancellation details), not unrelated activity noise.

**FR-RENT-06 — Future booking edit.** A future booked rental may be fully edited, including vehicle, customer, dates, and other permitted rental details, subject to validation and overlap rules.

**FR-RENT-07 — Active rental edit.** An active rental may have only its end date edited, and only while the current date is on or before its end date. Vehicle, customer, and start date are immutable for an active rental.

**FR-RENT-08 — Cancellation.** The Admin shall confirm cancellation and provide a cancellation reason. Every cancellation shall record the cancellation timestamp and reason. Cancelling an active rental sets its effective end date to the cancellation date; cancelling a booked rental frees its whole future date range and does not block availability.

**FR-RENT-09 — Completion.** The system shall automatically mark a rental completed on the day after its effective end date. The Admin may manually complete a rental only on or after its start date; completion before start is rejected.

### Dashboard

**FR-DASH-01 — Core metrics.** The dashboard shall show exactly these core metrics: total active vehicles, currently rented, available today, upcoming bookings, and today rental total. Counts and total shall reflect the current WIB date and persisted records.

## 5. Business rules and formulas

1. Dates use calendar days in WIB. Start and end are inclusive; end must not precede start. Duration is:

   `duration_days = (end_date - start_date) + 1`

   A same-day rental is therefore one day.
2. A new or edited rental cannot have a past start date. This applies to create and edit operations; the system must validate against the current WIB date.
3. A rental blocks a vehicle when its state is booked or active. Completed rentals do not block dates after their effective end date. A cancelled active rental blocks only through its cancellation/effective end date; a cancelled booked rental frees its whole future date range and does not block availability.
4. For the same vehicle, requested and existing ranges overlap when:

   `requested_start <= existing_effective_end AND requested_end >= existing_start`

   The rental being edited is excluded from its own conflict comparison. Boundary-touching dates conflict: a rental ending on a date conflicts with another starting on that date.
5. Archived vehicles cannot be selected for a new rental. Archive/restore does not erase rental history; restoring does not bypass overlap or date validation.
6. Daily rates are stored and calculated as integer USD cents and displayed as USD with two decimal places (`$0.00`). No floating-point monetary result may be exposed as a final price.
7. Before discount, `subtotal_cents = duration_days × daily_rate_cents`. If duration is greater than seven days, discount is 10% of the subtotal; otherwise discount is zero. The final amount is:

   `total_cents = round_half_up(subtotal_cents × 90 / 100)` when duration > 7, otherwise `subtotal_cents`.

   The rounded amount is stored as the rental’s price snapshot. Later vehicle-rate changes must not rewrite an existing snapshot.
8. A future booked rental is repriced only when its vehicle or dates change. Its new price uses the applicable current daily rate and the complete revised date range, then the same discount and half-up rounding rules. Editing only unrelated permitted details does not reprice it. Active, completed, and cancelled rental prices are never repriced.

### Worked examples

- A 1–1 March rental lasts 1 day. At `$50.00` (`5,000` cents), subtotal and total are `5,000` cents, or `$50.00`.
- A 1–8 March rental lasts 8 days. At `$100.00` (`10,000` cents), subtotal is `80,000` cents; 10% discount is `8,000` cents; total is `72,000` cents, or `$720.00`.
- A subtotal of `10,001` cents for a qualifying rental gives `9,000.9` cents after 10% discount and rounds half-up to `9,001` cents (`$90.01`).
- A rental from 10–12 June conflicts with an existing rental from 12–15 June because `10 <= 15` and `12 >= 12` are both true.
- A rental ending 20 June is active through 20 June and becomes automatically completed on 21 June. If cancelled on 18 June, its effective end is 18 June.

## 6. Acceptance criteria

- Admin can sign in with the seeded credential; unauthenticated/public account paths cannot access the application.
- Vehicle creation rejects blank values, invalid/non-cent rates, and duplicate plates after trim/uppercase normalization; valid records can be searched, filtered, paginated, archived, restored, and never hard-deleted.
- Brand and type are captured and filtered separately; year and color may be omitted.
- Customer creation/edit rejects missing required fields, invalid email/mobile values, and normalized duplicate email or phone; an existing customer can be reused across rentals.
- The rental form offers only non-archived vehicles available for the selected inclusive range, with an explanatory date-aware preview.
- Same-day rentals calculate one day; past starts, reversed dates, and invalid lifecycle actions are rejected with no partial mutation.
- Booked and active overlaps are rejected using the specified inclusive predicate, including boundary-touching dates; a rejected edit leaves the prior record intact.
- Rentals transition and can be manually managed only as permitted: active through end date, automatic completion the following day, manual completion on/after start, full edits only for future booked, and end-date-only edits for eligible active rentals.
- Cancellation requires confirmation and a reason, records a cancellation timestamp and lifecycle event, frees a booked rental's whole future date range, and truncates an active rental's effective end to the cancellation date.
- Rates and totals display as `$0.00`; qualifying rentals receive exactly the specified discount and half-up cent rounding; future booking vehicle/date changes recalculate while stored historical snapshots remain stable.
- Dashboard counts, history, filters, accessibility, and responsive behavior are usable in supported modern browsers.

## 7. Validation and error expectations

Validation shall be immediate where practical and repeated at save time. The user interface and messages shall use Indonesian wording, identify the offending field or business rule, preserve valid entered values, and avoid exposing credentials or sensitive internals. Conflict messages shall identify that the vehicle is unavailable for the requested dates and provide enough date context to correct the request. Failed archive, restore, cancel, create, or edit operations must not partially change related records. Destructive-looking actions require explicit confirmation; cancellation additionally requires a non-empty reason.

## 8. Nonfunctional requirements

- **Usability:** Common list, create, edit, archive/restore, and rental workflows should be clear and efficient for an internal operator; pagination and filters remain usable as records grow.
- **Accessibility:** Keyboard-operable controls, associated labels, visible focus, sufficient contrast, meaningful status text, understandable errors, and accessible confirmation dialogs are required.
- **Responsive compatibility:** Core workflows shall work at phone, tablet, and desktop widths in current major modern browsers without loss of information or action access.
- **Reliability:** Date, overlap, state, and money rules must be deterministic and consistent across preview, validation, save, and display.
- **Auditability:** Rental lifecycle history must explain state changes and cancellations, including the cancellation reason and effective date.
- **Quality target:** Unit tests cover formulas, normalization, dates, state transitions, and overlap; integration tests cover the principal admin workflows, persistence outcomes, and rejection paths.
- **Performance:** Normal list, filter, dashboard, and form interactions should provide timely feedback for a sensible initial fleet and rental dataset; exact service-level targets are deferred.

## 9. Data, privacy, and security requirements

- Store only data needed for vehicle, customer, rental, authentication, and lifecycle history. Customer name, email, and Indonesian mobile number are personal data and must be handled as confidential internal data.
- Enforce authentication for every protected operation and prevent unauthenticated access. The seeded credential must not be exposed in the UI, logs, documentation, or client-visible output.
- Protect credentials using appropriate secure handling and transmission practices; do not display passwords or sensitive authentication material.
- Normalize email and phone consistently for uniqueness while preserving an appropriate human-readable representation where useful. Do not leak whether another customer exists beyond necessary validation feedback.
- Apply authorization and validation to all state-changing actions, including archive, restore, cancellation, and edits. Prevent tampering with prices, states, dates, vehicle identity, or audit history.
- Retain customer, rental, and lifecycle history for operational traceability; customer records have no delete/archive operation. Retention, export, deletion of other data, backup, and recovery policies are not defined by this PRD and require a later approved decision.
- Do not send customer data or notifications externally in this release. Logging and error reporting must minimize personal data and secrets.

## 10. Assumptions and deferred decisions

- A single internal Indonesian deployment and one Admin credential are sufficient for the assessment release; multi-user roles and credential lifecycle are deferred.
- A rental has one vehicle, one customer, one date range, one daily rate snapshot, and one total; extensions, split rentals, add-ons, taxes, deposits, and currency conversion are deferred.
- Indonesian mobile phone input shall use the accepted canonical Indonesian mobile format, with consistent email case and whitespace normalization.
- Pagination size, filter facets, browser support matrix, and service-level targets may be selected during implementation without changing business rules.
- The exact event fields and retention duration for lifecycle history, and whether a cancellation before start permits immediate reuse on that date, require an approved product clarification if implementation needs more precision. The effective-end overlap rule remains authoritative.
- Architecture, storage technology, files, routes, APIs, and deployment topology are intentionally not prescribed by this PRD.

## 11. Traceability to `PRD`

| Source lines | Requirement coverage                                                                                                                                                             |
| ------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 5–7         | Internal fleet/rental product objective; vehicle and rental domains (FR-VEH, FR-RENT).                                                                                           |
| 13–15       | Vehicle CRUD fields, unique plate, status, table, search/filter (FR-VEH-01–06). Brand/type separation and archive/restore are approved clarifications of the master-data scope. |
| 17–18       | Rental selection of vehicle, start date, and end date (FR-RENT-01).                                                                                                              |
| 19–20       | More-than-seven-day automatic 10% discount and pricing rules (FR-RENT-01, business rules 1, 6–8).                                                                               |
| 21–22       | Mandatory rejection of overlapping rentals (FR-RENT-04; business rules 3–4).                                                                                                    |
| 25–29       | Technology and database remain intentionally implementation-independent; AI transparency is documented elsewhere as required by the source assessment.                           |
| 33–38       | README documentation obligation is outside this PRD’s product scope; this document records the product requirements and traceability only.                                      |
| 42–47       | Clean separation, correct business logic, clear analysis, and useful master/status/UX improvements inform the quality and usability requirements.                                |
| 51–54       | Submission deadline and format are assessment administration, not product behavior.                                                                                              |

## 12. Release acceptance checklist

- [ ] All FR-AUTH, FR-VEH, FR-MASTER, FR-CUST, FR-RENT, and FR-DASH requirements demonstrate acceptance evidence.
- [ ] Inclusive duration, same-day behavior, WIB date boundaries, overlap predicate, blocking states, and cancellation truncation have unit and integration evidence.
- [ ] Discount, half-up cent rounding, price snapshots, and future-booking repricing have unit evidence and displayed examples.
- [ ] Vehicle/customer normalization, required-field validation, duplicate rejection, archive/restore, and no-hard-delete behavior are verified.
- [ ] Lifecycle permissions and automatic completion are verified, including unchanged state after rejected edits.
- [ ] Search, filters, pagination, date-aware preview, dashboard counts, history, confirmations, responsive layouts, and accessibility checks pass.
- [ ] Security/privacy checks confirm protected access, safe credential handling, minimal logging, and no external notifications or public account paths.
- [ ] Parent/orchestrator reviews the implementation evidence against this approved baseline and records any approved revision separately.

## 13. Explicit approval

This PRD is the approved product baseline for implementation of the Fleet & Rental System. It is approved with the confirmed decisions stated above and with `PRD` as the authoritative assessment source. Any requirement change after this approval requires an explicit version revision and impact note. Validation ownership remains with the parent/orchestrator.
