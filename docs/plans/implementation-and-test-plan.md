# Fleet & Rental — Implementation and Test Plan

**Status:** Approved Brand-Type master-data implementation supplement  
**Phase:** 4 — Implementation plan and test design  
**Authority:** `PRD` is the authoritative assessment and business-rule source; `docs/PRD.md` v1.0 and the approved domain/architecture records constrain this plan.  
**Validation owner:** Parent/orchestrator  
**Gate:** Parent/orchestrator validation owner; Brand-Type supplement approved by task request.

## Brand-Type approved invariant

Brand has many Types; every Type has exactly one Brand. Type names are unique per Brand, not globally. Brand and Type have protected create/update/read routes only: no delete or archive operations. Vehicle create/update validates Brand/Type consistency at the application boundary and leaves the record unchanged on rejection; no composite foreign key is used. The UI handoff requires a Brand catalog, a dependent Type-by-Brand JSON endpoint, and vehicle controller/service catalog data.

## 1. Writing-plan intent and decision inputs

This document is a bounded implementation-and-test plan: it defines ordered work slices, dependencies, anticipated paths, risks, rollback boundaries, security/privacy obligations, and observable test evidence. It does not contain application code, exact schema, migrations, routes, view/style implementation, commands, or a new technical decision.

The parent selected and approved the **Laravel + PostgreSQL + Blade/Tailwind modular monolith** architecture and requested the Prompt 05 foundation after this plan is approved. That is an input from the parent, not a decision made by this document. The established layered OOP direction is: framework-free Domain; Application use cases, ports, authorization-facing contracts, and transaction orchestration; Infrastructure Laravel/Eloquent/PostgreSQL/clock/auth adapters; Presentation controllers, form requests, and Blade/Tailwind rendering. Dependencies point inward.

Parent-supplied official Laravel 13 research is recorded as planning input: Laravel 13 requires PHP 8.3+; the installer selects PostgreSQL and a testing framework; no starter kit is used because authentication is excluded from foundation; generated `.env` supports safe configuration; Blade/Tailwind/Vite are defaults; and `php artisan test` is available after scaffold. These are **verification-after-scaffold expectations**, not commands currently available in this repository. The generated manifest and configuration must be inspected before any command is run or documented as verified.

## 2. Current repository evidence and constraints

Current repository inspection found no application manifests, application scripts, tests, Laravel files, or runnable app commands. The only manifest inspected is `.opencode/package.json`, which describes repository tooling (`@opencode-ai/plugin`) rather than the application. Therefore current-state commands: **none**. After scaffolding, verify only generated `composer`/`package` scripts and configuration, then document the actual available Artisan test command after confirming it exists.

No exact generated-project paths are asserted below. All paths in work slices are anticipated/planned paths and must be reconciled against the generated scaffold before implementation.

## 3. Layered Laravel OOP boundary map

The planned implementation should map the approved architecture as follows without allowing framework or persistence concerns into Domain:

| Layer | Planned responsibility | Representative planned concerns |
|---|---|---|
| Domain | Business concepts and deterministic rules | Vehicle, Brand/Type, Customer, Rental, lifecycle states/history concepts, WIB date range and injected clock contracts, normalization, overlap, integer-cent pricing, snapshots, invariants |
| Application | Use cases and orchestration | Admin-facing commands/queries, authorization-facing contracts, availability preview, rental transaction boundary, save-time revalidation, conflict retry/failure handling, dashboard queries |
| Infrastructure | Framework and persistence adapters | Laravel/Eloquent mappings, PostgreSQL persistence, transaction and per-vehicle serialization, optional range/exclusion defense-in-depth, authentication adapter, clock adapter, environment configuration |
| Presentation | HTTP and server-rendered delivery | Controllers/form requests or equivalent Laravel boundary objects, Indonesian validation/error mapping, Blade/Tailwind screens, confirmation and accessible feedback |

Module seams remain Auth, Vehicles/Brand-Type, Customers, Rentals, Dashboard, and Lifecycle History. These are ownership boundaries, not a prescribed directory structure. Domain rules must not be placed in controllers, Blade views, or persistence models merely because those files are convenient.

## 4. Ordered work slices

### Slice 05 — Laravel foundation (Prompt 05; foundation only)

**Objective:** Establish the minimal Laravel project foundation and development configuration required by later slices, with no business features.

**Dependencies:** Parent approval of this Phase-4 plan; approved architecture and ADRs; generated Laravel 13 scaffold prerequisites; confirmed PHP 8.3+; generated manifests/configuration inspection before commands.

**Allowed scope:** Laravel scaffold; safe PostgreSQL configuration placeholders/example without secrets; empty module-boundary scaffolding for Domain/Application/Infrastructure/Presentation and the six conceptual modules, interfaces only where the scaffold/plan requires them; injected clock abstraction and test setup. No auth feature, vehicle/customer/rental behavior, dashboard, business rules, migrations, schema, routes, views, styles, or seeded credentials.

**Anticipated paths (planned, not existing):** generated Laravel project root files; environment example/configuration placeholders; planned domain/application/infrastructure/presentation boundary directories or namespaces; clock abstraction and adapter locations; test configuration/scaffolding locations. Exact paths must be discovered from the generated scaffold and recorded by the implementer.

**Risks:** Scaffold version/PHP mismatch; installer choices differ from parent research; accidental starter-kit/auth generation; unsafe environment examples; premature framework coupling; guessed commands. **Rollback:** discard the foundation slice as one bounded scaffold/configuration change, preserving documentation and repository tooling; do not retain partial feature wiring.

**Security/privacy:** Never write real credentials, customer data, or secrets. Keep generated environment examples safe; do not expose the seeded credential. Confirm no public account/API surface or auth feature was added.

**Verification expectation:** Inspect generated composer/package scripts and config. Only then verify the actual available scaffold checks, including documented `php artisan test` if generated configuration confirms it exists. No current command is claimed.

### Slice 06 — Admin authentication and master data (Prompt 06)

**Objective:** Implement only the protected Admin boundary and vehicle, separate Brand/Type, and reusable Customer capabilities.

**Dependencies:** Approved plan; accepted Prompt 05 diff/evidence; generated manifests/config/routes/migrations/tests inspected; approved Domain/Application boundaries; no rental or dashboard implementation prerequisite.

**Allowed scope:** Seeded environment credential handling without disclosure; protected Admin access; vehicle CRUD/search/filter/pagination/archive/restore/no-hard-delete/derived status; separate Brand and Type; normalized unique reusable customers with editable details and retention; focused tests/evidence for this slice.

**Anticipated paths (planned, not existing):** Auth, vehicle/master-data/customer Domain and Application locations; Infrastructure persistence/auth adapters and migrations; Presentation controllers/form requests/routes/views; slice-specific unit/integration/browser/accessibility test locations; generated config files. Exact paths remain scaffold-dependent.

**Risks:** Credential leakage; normalization ambiguity; hard-delete or archive-history regression; derived status becoming editable; scope creep into rentals/dashboard. **Rollback:** revert only Slice 06 paths and data setup as a coherent slice; preserve foundation and avoid destructive deletion of any approved historical data.

**Security/privacy:** Every operation protected by Admin authorization; customer name/email/mobile treated as confidential; no customer data in logs; no public registration/login/API; state-changing validation and authorization server-side.

### Slice 07 — Rental domain and persistence (Prompt 07)

**Objective:** Implement rental use cases, persistence, lifecycle, availability, pricing, snapshots, and conflict protection.

**Dependencies:** Approved plan; accepted Prompt 06 evidence; generated persistence/configuration inspected; Domain/Application boundaries; injected WIB clock; PostgreSQL transaction support confirmed from generated project configuration; test runner confirmed.

**Allowed scope:** Booked/active/completed/cancelled lifecycle; inclusive WIB dates; past/reversed rejection; exact blocking overlap predicate and boundary conflicts; cancellation effective end; completion and permitted edits; availability preview; integer-cent pricing, >7-day 10% discount, half-up rounding, snapshots, future-booking repricing; atomic/no-partial-mutation behavior; focused tests/evidence.

**Anticipated paths (planned, not existing):** Rental Domain value concepts/rules; Application rental commands/queries/ports and transaction coordinator; Infrastructure Eloquent/PostgreSQL adapters, persistence mappings, transaction/locking adapter, optional defense-in-depth conflict mechanism; Presentation rental/preview endpoints and validation boundary; rental unit/integration/concurrency test locations. Exact schema/DDL/routes are not specified here.

**Risks:** Race conditions; incorrect cancellation blocking; date-boundary/time-zone errors; floating-point prices; snapshot mutation; lifecycle permission leaks; transaction rollback gaps. **Rollback:** revert the rental slice and its persistence changes as one bounded unit; preserve earlier master data and do not perform destructive data repair without a separately approved recovery action.

**Security/privacy:** Admin authorization on every rental state change; prevent tampering with vehicle, dates, lifecycle, prices, effective end, or history; minimize customer data in errors/logs; no external notifications/payments/API.

### Slice 08 — Admin UI and dashboard (Prompt 08)

**Objective:** Deliver the approved accessible, responsive Blade/Tailwind internal workflows over the implemented backend without changing domain rules.

**Dependencies:** Approved plan; accepted Slices 05–07 evidence; generated routes/views/assets/scripts inspected; server-side authorization and validation available; dashboard query semantics available.

**Allowed scope:** Sign-in, dashboard’s exactly five core metrics, vehicle/customer/rental lists and forms, search/filter/pagination, date-aware vehicle preview, Indonesian errors, rental history, and confirmations for archive/restore/cancel. No SPA, public/customer UI/API, new business rules, payments, notifications, or unrelated redesign.

**Anticipated paths (planned, not existing):** Presentation controllers/form requests/routes and Blade/Tailwind template/assets locations; dashboard query/read-model location; browser/accessibility/integration test locations; generated Vite/Tailwind configuration paths. Exact paths and components must be discovered after scaffold.

**Risks:** Client/display logic diverging from Domain/Application; inaccessible confirmations/focus; leakage of credentials or personal data; dashboard metrics silently adding inclusion rules; responsive information loss. **Rollback:** revert only presentation/dashboard changes, retaining approved backend semantics; remove incomplete screen wiring without changing persisted history.

**Security/privacy:** Server-side authorization and validation remain authoritative; protect credentials and customer information; Indonesian errors must not expose internals; confirmation actions must not bypass authorization or atomic use cases.

## 5. Detailed test and evidence matrix

The following is test design, not created test code. The actual runner, paths, scripts, and commands are selected only after scaffolding and manifest discovery.

| Area | Requirements/invariants | Unit evidence | Integration/persistence evidence | Browser/accessibility evidence | Concurrency/clock evidence |
|---|---|---|---|---|---|
| Admin access | FR-AUTH-01–02; sole Admin; no public accounts/API | Auth policy/authorization boundary contract | Unauthenticated rejection; seeded credential safe handling; protected state changes | Sign-in, protected navigation, no public account path; labels/focus/errors | Injected/current WIB clock where date-dependent; no credential logging |
| Vehicle and status | FR-VEH-01–06; plate invariant; archive/no hard delete/derived status | Required fields, trim/uppercase duplicate plate, derived status rules | CRUD persistence, archive/restore confirmation, history links, no hard delete, rejected operation unchanged | Search/filter/pagination, status meaning, keyboard/table labels, responsive operation | Active rental covering injected today yields `di-sewa`; archive precedence; concurrent archive/rental behavior as authorized |
| Brand/type | FR-MASTER-01 | Separate value validation and identity | Separate persistence/link/filter behavior | Separate fields and filters, labels/errors | N/A unless persistence race is identified |
| Customer | FR-CUST-01–05; normalization/uniqueness/retention invariants | Required fields, email/phone normalization and duplicate rejection using only approved canonical facts | Edit preserves rental links; reuse; no delete/archive; no partial mutation | Accessible forms, Indonesian validation, confidential error presentation | Concurrent normalized duplicate attempts do not both succeed |
| Dates and duration | FR-RENT-01–03; business rule 1–2; domain date invariants | WIB boundaries, end-before-start rejection, past-start rejection; same-day = 1; exactly 7 = no discount threshold; >7 qualifies; injected clock | Save-time repeat validation; persisted date values; rejected create unchanged | Date form and preview explain invalid/unavailable range without losing valid values | Injected WIB date at midnight/day boundary; concurrent save revalidates |
| Overlap/blocking | FR-RENT-03–04; business rules 3–4; exact predicate | Boundary-touching overlap; non-overlap; self-exclusion on edit; booked/active blocking; completed/non-blocking after end; cancelled booked vs cancelled active | Transaction rollback; availability preview advisory; conflicting create/edit leaves prior record intact | Unavailable vehicle/date context and accessible error | Concurrent conflicting writes for one vehicle serialize; one succeeds and one rejects; per-vehicle lock/recheck evidence |
| Lifecycle | FR-RENT-02, 05–09; effective-end invariants | Active/completed/cancelled definitions; automatic completion day after effective end; manual completion on/after start only; edit permissions | Cancellation reason/timestamp/effective end/history; booked cancellation frees range; active cancellation truncates; no partial mutation | Confirm cancel/archive/restore; reason required; history readable and accessible | Injected WIB date across start/end/effective-end transitions; concurrent lifecycle conflict behavior |
| Pricing | FR-RENT-01, 06–08; business rules 6–8 | Integer USD cents; same-day; exactly 7; 8-day discount; subtotal 10,001 → half-up 9,001; no floating final price | Snapshot persists; vehicle rate change stable; future booked vehicle/date change reprices full revised range; unrelated edit does not; active/completed/cancelled never reprice | `$0.00` display, discount/total clarity, accessible money/error text | Concurrent edit/save cannot corrupt snapshot or accept stale vehicle/date price |
| Dashboard | FR-DASH-01; persisted/current-WIB derived metrics | Query/derivation contracts for exactly total active vehicles, currently rented, available today, upcoming bookings, today rental total; no invented inclusion detail | Metrics reflect persisted records and current WIB date, archive and blocking semantics | Exactly five metrics visible, understandable status text, keyboard/focus/contrast/responsive checks | Injected WIB date at boundaries; refresh/read consistency while rental state changes |
| Cross-cutting rejection | PRD §§6–7; no partial mutation; security/privacy | Validation contracts preserve valid values | Failed create/edit/archive/restore/cancel changes no related records/history/state | Errors identify field/rule in Indonesian without internals or secrets | Rollback and retry evidence under conflicting writes |

### Required named cases

At minimum, later evidence must explicitly identify: duration greater than 7 days; exactly 7 days with no discount; same-day duration one; subtotal 10,001 half-up result 9,001 cents; boundary overlap where one range ends as another starts; cancelled booked versus cancelled active blocking differences; past start; no partial mutation after failed operations; future booked vehicle/date repricing versus unrelated edit and immutable active/completed/cancelled snapshots; and an injected WIB date for current-day/status/lifecycle boundaries.

## 6. Commands and verification policy

**Current repository:** no application commands exist. `.opencode/package.json` is tooling-only; no application `composer.json`, `package.json`, Artisan file, test configuration, or scripts are present.

**After Slice 05 scaffold:** inspect generated `composer` scripts, `package` scripts, PHP/Laravel version/configuration, database configuration, and test configuration. Run only commands discovered there and assigned by the approved plan. The parent’s Laravel 13 research suggests `php artisan test` after scaffold, but it must first be confirmed to exist in generated configuration; this plan does not claim it is currently runnable. Record exact command/output only after execution.

## 7. Assumptions and deferred questions

- Parent’s architecture selection/approval is an input; the plan does not add or alter that decision.
- Prompt 05 deliberately excludes authentication and all business features; auth begins in Prompt 06.
- Exact generated Laravel paths, installer choices, test framework, scripts, and configuration are unknown until scaffold and must not be guessed.
- Exact event fields/retention, cancellation-before-start reuse, canonical email/phone forms, pagination/filter facets, browser matrix, service targets, and backup/recovery policy remain deferred from PRD §10 and architecture records.
- Exact schema, migrations, DDL, routes, controller maps, views, component structure, packages, and commands are implementation-time decisions only where authorized by later approved artifacts.

## 8. Traceability summary

The slices collectively target FR-AUTH-01–02, FR-VEH-01–06, FR-MASTER-01, FR-CUST-01–05, FR-RENT-01–09, and FR-DASH-01. The test matrix covers domain invariants and PRD business rules 1–8: inclusive WIB dates/duration; no past starts; blocking/cancellation; exact overlap; archive; integer cents; >7-day discount and half-up rounding; and snapshot/repricing. Assessment coverage includes vehicle CRUD/search/filter, rental form, discount, overlap rejection, clean separation, business correctness, analysis, and engineering improvements.

## 9. Phase-4 handoff record

- **Phase / owner:** 4 — Implementation plan and test design / implementation planner and test-design owner
- **Inputs read:** `AGENTS.md`; `PRD`; `docs/PRD.md`; `docs/DEVELOPMENT-WORKFLOW.md`; `docs/domain/fleet-rental-domain-semantics.md`; `docs/ARCHITECTURE.md`; `docs/ADR/001-modular-monolith.md` through `docs/ADR/006-testing-boundary.md`; `docs/architecture/architecture-options.md`; `prompts/04-implementation-and-test-plan.md`; `prompts/05-laravel-foundation.md`; `prompts/06-auth-and-master-data.md`; `prompts/07-rental-domain-and-persistence.md`; `prompts/08-admin-ui.md`; `README.md`; current manifest/repository inspection including `.opencode/package.json`.
- **Parent inputs:** Parent-selected and approved Laravel + PostgreSQL + Blade/Tailwind architecture; parent-supplied Laravel 13 official-doc research summary; request for Prompt 05 foundation after plan approval.
- **Output:** `docs/plans/implementation-and-test-plan.md`
- **Changed paths:** `docs/plans/implementation-and-test-plan.md` (created); `docs/README.md` (appended).
- **Evidence:** Ordered Prompt 05–08 slices, layered boundary map, anticipated planned paths, dependencies, risks, rollback, security/privacy, command-discovery policy, detailed test matrix, named edge cases, traceability, assumptions, and deferred questions.
- **Unresolved questions:** §7; no answers invented.
- **Gate decision:** **HOLD** pending explicit parent approval of this plan and test matrix.
- **Approver / timestamp:** Parent/orchestrator / pending; plan date 24 August 2026 (WIB).
- **Next-gate constraint:** Prompt 05 foundation cannot begin until the parent explicitly approves this Phase-4 plan; later slices require their stated preceding handoffs and approvals.
