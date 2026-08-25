# Fleet & Rental — Architecture Options Analysis

**Status:** Options analysis; no architecture selection is approved  
**Authority:** `PRD` remains the authoritative assessment and business-rule source; approved `docs/PRD.md` and the approved domain semantics constrain this analysis.  
**Validation owner:** Parent/orchestrator  
**Gate:** **HOLD** pending parent review and an explicit architecture decision. Producing this options document is not approval of any option.

## 1. Inputs, scope, and decision boundary

The parent explicitly approved the Phase-2 domain gate in conversation and approved this options-analysis structure. That approval authorizes analysis only. Architecture selection remains **HOLD**; no ADR, implementation plan, application file, schema, migration, route, test, view, configuration, manifest, or command is authorized by this document.

This document compares viable shapes against the requirements and domain semantics without claiming an approved framework, database, deployment topology, or frontend approach. The assessment permits a monolith or decoupled API plus frontend and leaves stack/database choice free. The intended Laravel/PostgreSQL/Blade-Tailwind direction is therefore treated as an unapproved candidate, not as a decision.

## 2. Shared domain/module boundaries

All options should preserve the same conceptual boundaries, regardless of process shape or technology:

| Boundary | Responsibility | Important domain contract |
|---|---|---|
| Auth | Seeded internal Admin authentication and protected access | Sole Admin actor; no public registration, customer login, password reset, or public API |
| Vehicles and brand/type | Separate Brand and Type values; vehicle CRUD, normalized plate, archive/restore, search/filter, derived status | Archived vehicles cannot be newly rented; current status is derived from archive and today’s blocking active rental |
| Customers | Reusable retained customer master, normalized uniqueness, editable details | Customer links/history remain intact; no customer delete/archive |
| Rentals | Creation, editing, date-aware preview, pricing, conflict rejection, list/filter/history | One vehicle, one customer, inclusive WIB range, lifecycle state, and immutable historical price snapshot |
| Dashboard | Exactly the five FR-DASH-01 core metrics | Derive from persisted records using current WIB date and established archive, blocking, effective-end, and inclusive-date semantics; do not invent `today rental total` inclusion details |
| Lifecycle history | Rental creation, relevant state changes, and cancellation details | Cancellation reason, timestamp, and effective date are retained; unrelated activity noise is not part of rental history |

The boundaries are analytical seams, not a prescribed folder layout, schema, route set, or service decomposition.

## 3. Cross-option persistence and consistency implications

The requirements are most sensitive to the correctness of a state-changing rental operation, not to the number of deployable processes. Any selected option must make the following one logical operation reliable:

1. Validate current WIB date, inclusive start/end ordering, archived status, lifecycle permission, customer/vehicle identity, and the exact overlap predicate:

   `requested_start <= existing_effective_end AND requested_end >= existing_start`

2. Consider only blocking rentals (`booked` and `active`, plus the stated effective-end behavior for cancelled active rentals); exclude the rental itself during edit comparison; preserve boundary-touching conflicts.
3. Prevent concurrent successful writes from both accepting conflicting ranges for one vehicle. A generalized transactional per-vehicle serialization strategy is an analytical implication, not a selected implementation mechanism.
4. Apply all related state, effective-end, lifecycle-history, and price-snapshot changes atomically. A rejected create/edit/archive/restore/cancel operation must not partially mutate related records.
5. Preserve integer USD-cent snapshots. Vehicle-rate changes must not rewrite existing snapshots; only future booked vehicle/date changes trigger repricing under the approved formula.

A PostgreSQL-backed design could analytically consider native range and exclusion capabilities for defense-in-depth around per-vehicle date conflicts, while still applying the domain’s blocking-state and cancellation semantics. This is a capability comparison, not a schema, migration, or database selection. Any option using another persistence technology must demonstrate equivalent conflict protection and atomicity without weakening the inclusive/effective-end rules.

Read previews may be advisory: save-time validation remains authoritative because another operation may commit between preview and save. Dashboard metrics and derived vehicle status must read persisted state consistently with the current WIB date.

## 4. Option A — Intended Laravel/PostgreSQL/Blade-Tailwind modular monolith (unapproved)

### Shape

One deployable server-rendered application with domain-oriented modules for Auth, Vehicles/Brand-Type, Customers, Rentals, Dashboard, and Lifecycle History. Laravel, PostgreSQL, Blade, and Tailwind are the intended candidate technologies described by the task request, but none is approved and no exact project structure is proposed here.

### Fit and benefits

- A single application boundary can keep rental validation, transaction coordination, lifecycle events, and price snapshots close together.
- Server-rendered workflows naturally fit the internal Admin use case, list/filter/form requirements, confirmation actions, and progressive enhancement.
- A modular monolith can provide clear ownership boundaries without the operational overhead of multiple independently deployed services.
- PostgreSQL is a credible fit for transactional integrity and may offer range/exclusion capabilities worth evaluating for overlap defense-in-depth; this remains an option-level implication only.
- Blade/Tailwind could support accessible, responsive HTML workflows, but accessibility remains an implementation acceptance obligation rather than a property guaranteed by the option.

### Costs and risks

- The candidate stack is not yet approved; choosing it prematurely would bypass the architecture gate and later ADR process.
- A modular monolith can become coupled if module boundaries are only nominal. Rental writes must not be scattered across unrelated feature code.
- Server-rendered interaction patterns may require deliberate handling for date-aware availability preview and useful validation feedback.
- One deployable unit reduces topology complexity but does not remove concurrency, credential protection, backup, observability, or operational responsibility.

### Requirement implications

This option can plausibly satisfy authentication, vehicle/customer master data, rental lifecycle, dashboard, accessibility, and assessment overlap/discount requirements if later design and implementation provide evidence. It does not itself prove those requirements.

## 5. Option B — Credible server-rendered modular monolith alternative

### Shape

A server-rendered modular monolith using a credible alternative such as Django with PostgreSQL, or Node/Nest with a server-template approach. The alternatives are deliberately presented as a family of candidates rather than a selected stack. The same conceptual modules and one logical transactional write boundary apply.

### Fit and benefits

- Retains the operational simplicity and cohesive transaction boundary of a monolith while avoiding dependence on Option A’s unapproved ecosystem.
- Mature server-rendered patterns can support Admin-only forms, filters, pagination, confirmations, accessible HTML, and history views without requiring a separate public API.
- PostgreSQL remains an optional persistence pairing for the Django alternative, and the same analytical range/exclusion and per-vehicle serialization considerations apply; no database is selected here.
- A server-template Node/Nest alternative could provide a familiar JavaScript/TypeScript operational model while keeping presentation and rental writes in one deployable unit.

### Costs and risks

- “Django + PostgreSQL” and “Node/Nest templates” have materially different conventions; selecting between them needs explicit evaluation rather than treating them as interchangeable.
- Framework defaults may encourage coupling between request handlers and persistence unless the domain boundaries are actively protected.
- The chosen alternative’s transaction, locking, validation, authentication, and accessibility facilities must be verified before implementation.
- A less familiar candidate for the team could increase delivery and maintenance risk even if its theoretical fit is similar.

### Requirement implications

This option can meet the same PRD/domain requirements as Option A in principle, but evidence must later establish deterministic WIB handling, exact overlap protection, no partial mutation, snapshots/repricing, security, accessibility, lifecycle history, and dashboard derivation. No candidate within this option is approved.

## 6. Option C — API + SPA (decoupled frontend)

### Shape

An authenticated API owns the domain modules and persistence; a separate single-page application consumes API capabilities for Admin workflows. The API and SPA are separate delivery concerns, while the rental conflict and pricing rules remain server-authoritative.

### Fit and benefits

- Rich date-aware availability preview, filters, dashboard refresh, and inline validation can be presented without full-page navigation.
- A clear API boundary can make domain operations explicit and potentially support future internal clients, although a public API is out of scope for this release.
- Frontend accessibility and responsive behavior can be tested as a dedicated client concern.

### Costs and risks

- Two application surfaces increase authentication/session, CSRF or equivalent request-protection, authorization, validation-error, deployment, monitoring, and version-compatibility complexity.
- The SPA must not duplicate authoritative overlap, lifecycle, pricing, or effective-end rules; duplicated logic risks divergence from the API.
- The assessment’s out-of-scope public API requirement means the API would need to remain an internal implementation boundary, not a public product surface.
- More moving parts increase operational and test burden for a single-role internal application and may not justify the benefit at the initial scope.
- Accessibility can regress across client-side state, focus management, dialogs, and error presentation unless treated as a first-class acceptance concern.

### Requirement implications

This option can satisfy the PRD if every state-changing API operation enforces Admin authorization, atomic persistence, exact date/overlap rules, snapshots, and no partial mutation, while the SPA accurately exposes all required workflows and accessibility behavior. It is not selected or approved.

## 7. Comparative assessment

| Criterion | Option A: intended server monolith | Option B: alternative server monolith | Option C: API + SPA |
|---|---|---|---|
| Domain cohesion and rental atomicity | Strong fit with one deployable boundary; still requires explicit transaction/concurrency design | Strong fit; framework transaction behavior must be verified | Strong server-side fit, but more boundary/error coordination |
| Inclusive overlap/blocking/effective end | Centralized enforcement is straightforward in principle | Same potential; candidate-specific persistence behavior remains open | Central API enforcement is mandatory; client preview is advisory |
| Snapshots and no partial mutation | Cohesive write workflow is a benefit | Cohesive write workflow is a benefit | Requires disciplined API transaction and client error handling |
| Admin workflow usability | Good fit for forms, lists, filters, confirmations | Good fit; depends on selected template ecosystem | Rich interactions possible, with greater UI complexity |
| Security surface | One protected application surface | One protected application surface | API, SPA, browser boundary, and token/session concerns |
| Accessibility | Server HTML can provide a strong baseline; must be verified | Same baseline potential; must be verified | Highest client-state complexity; must be verified |
| Testing burden | Domain, integration, and browser workflow coverage in one app | Similar, with candidate-specific tooling | Server/API plus SPA and end-to-end integration coverage |
| Operations | One deployable application and persistence dependency | One deployable application and persistence dependency | Multiple deployable concerns and coordinated releases |
| Assessment fit | High potential fit, pending approval and evidence | High potential fit, pending candidate selection | Valid per assessment, but likely more complexity than initial scope |
| Principal risk | Prematurely treating intended direction as approved | Ambiguous candidate family and unfamiliar conventions | Duplication, operational overhead, and client/API divergence |

These are trade-off findings, not approval criteria or an architecture decision.

## 8. Security, testing, accessibility, and operations obligations

These obligations apply to every option and are not solved merely by selecting a shape:

- **Security:** Protect every application operation behind the single Admin role; keep the seeded credential out of UI, logs, documentation, and client-visible output; protect personal customer data; authorize and validate archive, restore, cancellation, and edits; minimize sensitive logging; provide no public account/API surface.
- **Testing:** Later test design must cover formulas, normalization, WIB dates, same-day duration, lifecycle transitions/permissions, inclusive boundary overlap, effective-end cancellation, archive/restore, snapshots/repricing, no-partial-mutation rejection paths, dashboard metrics, and principal Admin workflows. Exact commands are deferred because no application manifests/scripts exist.
- **Accessibility:** Later implementation must provide keyboard-operable controls, associated labels, visible focus, sufficient contrast, meaningful statuses, understandable Indonesian errors, accessible confirmations, and responsive operation in supported modern browsers.
- **Operations:** The later selected architecture must define, outside this options artifact, how credentials, persistence backups/recovery, logs, error reporting, deployment, and monitoring are handled without external customer communications. PRD retention/export/deletion policies and exact service-level targets remain deferred.

## 9. Traceability

| Requirement/domain area | Traceability in this analysis |
|---|---|
| Admin-only authentication and no public account/API features | PRD FR-AUTH-01–02; §§1, 8 |
| Vehicle CRUD, normalized plate, listing/filtering, archive/restore, derived status, no hard delete | PRD FR-VEH-01–06; domain §§1, 3–4; shared Vehicles boundary |
| Separate Brand and Type | PRD FR-MASTER-01; domain §§1–2; shared boundary |
| Customer creation/edit, normalization, reuse, retention | PRD FR-CUST-01–05; domain §§1–2; shared Customers boundary |
| Rental creation, states, preview, overlap rejection, history, edits, cancellation, completion | PRD FR-RENT-01–09; domain §§3–4; shared Rentals/Lifecycle History boundaries |
| WIB dates, inclusive duration, exact overlap, blocking states, effective end, no partial mutation | PRD business rules 1–5 and PRD §§6–7; domain §§3–4, 6–7; §3 of this analysis |
| Integer USD cents, >7-day 10% discount, half-up rounding, snapshots/repricing | PRD business rules 6–8; domain §§3, 6; §3 of this analysis |
| Dashboard exactly five metrics from persisted records/current WIB date | PRD FR-DASH-01; domain §9 and shared Dashboard boundary |
| Security, privacy, accessibility, reliability, auditability, performance | PRD §§7–9; §8 of this analysis |
| Assessment stack freedom, rental form, discount, overlap, documentation | `PRD` lines 5–7, 17–29, 33–47; §§1, 4–6 of this analysis |

The analysis covers all eight PRD business rules and the PRD functional areas relevant to architecture. It does not convert any deferred business question into a technical decision.

## 10. Deferred questions and decision inputs

The following remain open and must not be silently resolved by architecture selection:

1. Which candidate stack and persistence technology, if any, will be selected?
2. What exact lifecycle-history event fields and retention duration are approved?
3. Does cancellation before a booked rental’s start permit immediate reuse on that cancellation/start date? The effective-end overlap rule and non-blocking booked-cancellation rule remain authoritative, but the edge requires clarification.
4. What exact accepted canonical forms apply to Indonesian mobile phone and email normalization?
5. What pagination size, filter facets, browser support matrix, service-level targets, and operational backup/recovery policy are approved?
6. If PostgreSQL is selected, will range/exclusion capabilities be used as defense-in-depth alongside application/domain validation? If another persistence technology is selected, what equivalent concurrency guarantee will be approved?
7. For Option C, what internal-only API boundary and authentication/session model are acceptable without creating an out-of-scope public API?

## 11. Recommendation as Prompt-03 input (non-binding)

**Non-binding recommendation:** Carry **Option A** forward as the preferred candidate for Prompt 03 because a modular monolith appears proportionate to the single internal Admin workflow, keeps rental writes cohesive, and can plausibly satisfy the domain’s transaction and auditability needs. This is only an analysis input. It is not an approved Laravel, PostgreSQL, Blade, Tailwind, or modular-monolith decision. Option B and Option C remain viable alternatives until the parent explicitly decides.

## 12. Evidence and phase-3 handoff

- **Phase / owner:** 3 — Architecture options / architecture analyst
- **Inputs read:** `AGENTS.md`; `PRD`; `docs/PRD.md`; `docs/DEVELOPMENT-WORKFLOW.md`; `docs/domain/fleet-rental-domain-semantics.md`; `prompts/02-architecture-options.md`; `README.md`; current repository/manifests inspection, including `.opencode/package.json`.
- **Repository evidence:** Current manifests expose only `.opencode` tooling (`@opencode-ai/plugin`); no application manifests or app scripts exist. No runnable application commands exist in the current manifests. Existing repository documentation confirms implementation has not started and architecture remains deferred.
- **Output path:** `docs/architecture/architecture-options.md`
- **Changed paths:** `docs/architecture/architecture-options.md` and `docs/AI-USAGE.md` only.
- **Evidence produced:** Option comparison, shared boundaries, persistence/concurrency implications, security/testing/accessibility/operations obligations, risk trade-offs, PRD/domain traceability, deferred questions, and non-binding Prompt-03 recommendation.
- **Unresolved questions:** §10; no technical or business answer is invented.
- **Gate decision:** **HOLD** pending parent review and explicit architecture decision.
- **Approver / timestamp:** Parent/orchestrator / pending; analysis date 24 August 2026 (WIB).
- **Next-gate constraint:** No ADR or implementation work may begin from this artifact alone.
