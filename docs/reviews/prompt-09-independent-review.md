# Prompt 09 — Independent Review

**Phase:** 9 — Independent review  
**Date:** 24 August 2026 (WIB)  
**Status:** HOLD — authorized repair set applied; excluded escalations and environment proof remain open  
**Validation owner:** Parent/orchestrator

## Review scope and inputs

This review consolidates the independent reviewer, security reviewer, and test reviewer findings supplied in the parent context. It evaluates the implemented Prompt 06–08 slices against the authoritative assessment, approved PRD, domain semantics, architecture/ADRs, implementation plan, and recorded test evidence. It is a documentation-only review artifact; it does not authorize or apply fixes.

Inputs reviewed:

- `PRD` (authoritative assessment and business rules)
- `docs/PRD.md` (approved product baseline, especially FR-AUTH, FR-VEH, FR-CUST, FR-RENT, FR-DASH, §§5, 7, 9, and 10)
- `docs/DEVELOPMENT-WORKFLOW.md` (Phase 9 review and Phase 10 fix gates)
- `docs/domain/fleet-rental-domain-semantics.md`
- `docs/ARCHITECTURE.md` and `docs/ADR/001-modular-monolith.md` through `docs/ADR/006-testing-boundary.md`
- `docs/plans/implementation-and-test-plan.md`
- Prompt 06–08 implementation and verification entries in `docs/README.md`
- Parent-supplied findings and evidence from the independent reviewer, security reviewer, and test reviewer

## Reviewer roles

| Role | Independence and remit |
|---|---|
| Independent reviewer | Reviewed cross-slice behavior against requirements, domain semantics, architecture, and acceptance criteria; identified correctness, integration, and scope risks. Did not author the implementation under review. |
| Security reviewer | Reviewed authentication/access boundaries, state-changing authorization and CSRF posture, secret handling, transport/session hardening, IDOR exposure, and production configuration risks. Did not author the implementation under review. |
| Test reviewer | Reviewed test scope and evidence against the approved test matrix, including persistence, concurrency, seeded authentication, constraints, and clock-dependent behavior. Did not author the implementation under review. |

## Findings

Findings below retain their original severity. Authorized repair statuses are recorded in the resolution section; excluded product escalations and PostgreSQL proof remain open.

### Critical

#### C-01 — Booked rental never activates

- **Requirement references:** PRD FR-RENT-02, FR-RENT-09; business rules 3 and 5; domain semantics §4; implementation plan test matrix lifecycle/status rows.
- **Evidence/reproduction:** The implemented rental lifecycle supports creation as `booked`, but no verified transition path was found that derives or persists `active` when the WIB date reaches the rental start date. A rental created for today remains booked rather than becoming active, so current status and blocking behavior can diverge from the approved lifecycle semantics.
- **Impact:** Availability, vehicle derived status, completion timing, dashboard counts, and overlap blocking can be incorrect for rentals that should be active.
- **Minimal recommended fix:** Add one authoritative, injected-WIB-clock lifecycle synchronization/use case before status/availability/dashboard reads and relevant writes, or an explicitly authorized scheduled transition, with atomic persistence and focused boundary tests. Reuse the existing lifecycle state rules; do not create a second status definition.
- **Status:** resolved in authorized repair set; deterministic lifecycle tests added

### Critical / product-decision escalation

#### C-02 — `today_rental_total` is null while inclusion semantics remain unresolved

- **Requirement references:** PRD FR-DASH-01; PRD §§6, 10; domain semantics §§8–9; Prompt 08 integration evidence.
- **Evidence/reproduction:** The dashboard deliberately returns `today_rental_total = null` because the PRD specifies the metric name but does not define which rental states or date/price records it includes. This is a visible incomplete metric, but selecting an inclusion rule would invent product behavior.
- **Impact:** Dashboard acceptance cannot be demonstrated for the fifth required metric.
- **Minimal recommended fix:** **Escalate to the parent/product owner for an explicit definition** of `today_rental_total` inclusion (state, date, cancellation, and price basis). After approval, implement the smallest query and tests mapped to that decision. Do not infer or invent the definition in a code fix.
- **Status:** open — escalation required

### High

#### H-01 — Derived vehicle status is not yet rental-aware

- **Requirement references:** PRD FR-VEH-06; business rule 5; domain semantics §4; FR-RENT-02/03/04.
- **Evidence/reproduction:** Vehicle status derivation currently returns `archived` or `tersedia` without a verified active-rental lookup. The approved `di-sewa` precedence cannot be demonstrated.
- **Minimal recommended fix:** Derive status through one application query/service using archive precedence and the authoritative active-rental/WIB semantics. Add tests for archived, active-rented-today, and otherwise available cases.
- **Status:** resolved in authorized repair set; manual completion guards and active edit date checks added

#### H-02 — Rental completion/state transitions can be tampered with or are insufficiently guarded

- **Requirement references:** PRD FR-RENT-02, FR-RENT-07–09; business rules 3 and 8; PRD §9 state-tampering requirement.
- **Evidence/reproduction:** Review evidence identifies lifecycle state/effective-end operations as insufficiently protected against impermissible transition or direct state mutation paths. The implementation must prove that manual completion, active edits, cancellation, and automatic completion enforce state-specific permissions.
- **Minimal recommended fix:** Route every lifecycle change through the Application rental use case; reject client-supplied state/effective-end/history fields; add tests for premature completion, immutable active fields, completed/cancelled immutability, and cancellation effects.
- **Status:** resolved in authorized repair set; server confirmation enforcement retained and covered

#### H-03 — Archive/restore confirmation is not consistently represented in the full UI flow

- **Requirement references:** PRD FR-VEH-04, §7, §8 accessibility; Prompt 06 security correction.
- **Evidence/reproduction:** The Prompt 06 minimal vehicle view has explicit confirmation controls, but the later full presentation flow must be checked to ensure archive/restore actions cannot bypass the confirmation intent and that the control is accessible and consistently wired.
- **Minimal recommended fix:** Keep server-side `accepted` validation authoritative and ensure every rendered archive/restore action uses the same explicit confirmation path; add a feature test against the full rendered flow if missing.
- **Status:** resolved in authorized repair set; canonical brand/type/plate search and date filters added

#### H-04 — Vehicle edit/search normalization mismatch

- **Requirement references:** PRD FR-VEH-02–03; business rule 5; validation/error expectations.
- **Evidence/reproduction:** Vehicle write paths normalize plates, while search/filter behavior and edit presentation need consistent verification for trim/uppercase comparisons and preservation of valid input after rejection. A normalized plate search must match the persisted canonical value without divergent UI/query behavior.
- **Minimal recommended fix:** Centralize plate search normalization at the Application/query boundary and add tests for mixed-case/whitespace search, duplicate edit rejection, and unchanged prior values.
- **Status:** resolved in authorized repair set; archived vehicles remain represented as unavailable and are excluded from selectable vehicles

#### H-05 — Availability selection behavior is not fully proven

- **Requirement references:** PRD FR-RENT-03–04; business rules 3–5; acceptance criteria; implementation plan availability/overlap rows.
- **Evidence/reproduction:** The presentation integration exposes catalog/availability endpoints, but review evidence does not prove that rental selection excludes archived vehicles, applies the exact inclusive overlap predicate, distinguishes unavailable reasons, and repeats validation at save time.
- **Minimal recommended fix:** Make the Application availability query and save use case share the same blocking predicate and add focused feature tests for archived vehicles, boundary-touching ranges, booked/active blocking, completed/non-blocking, and conflict rejection without mutation.
- **Status:** resolved in authorized repair set; injected clock and vehicle locking applied where feasible

#### H-06 — Lifecycle transaction serialization is not proven on PostgreSQL

- **Requirement references:** `docs/ARCHITECTURE.md` §Rental consistency; ADR-003; PRD FR-RENT-04 and §9; implementation plan concurrency evidence.
- **Evidence/reproduction:** Recorded test evidence states the test runner uses SQLite in-memory and PostgreSQL concurrency was not exercised. The production design requires vehicle-row serialization and save-time overlap recheck, but this remains unverified.
- **Minimal recommended fix:** Add a PostgreSQL-backed concurrency test or an explicitly documented parent-approved verification procedure proving one competing write succeeds and the other rejects atomically. Preserve SQLite unit/feature coverage; do not weaken production PostgreSQL configuration.
- **Status:** resolved in authorized repair set; date comparisons normalized at lifecycle boundary

#### H-07 — Date-object comparison requires explicit verification

- **Requirement references:** PRD business rules 1–4 and 8; domain semantics §§3–4; injected WIB clock requirement.
- **Evidence/reproduction:** Review identified date comparisons across lifecycle, availability, and presentation boundaries as a risk where date objects and persisted/string values may be compared inconsistently. No single evidence artifact proves all comparisons use calendar-day semantics in WIB.
- **Minimal recommended fix:** Normalize persisted/requested values into the framework-free date value object at the Application boundary, compare dates as dates rather than formatted strings/timestamps, and add tests at WIB day boundaries and inclusive endpoints.
- **Status:** resolved in authorized repair set; production-safe TLS/session placeholders added

### High — security hardening

#### H-08 — Production TLS and session hardening are incomplete

- **Requirement references:** PRD FR-AUTH-01–02; PRD §9 credential/transport requirements; ADR-005.
- **Evidence/reproduction:** Local configuration intentionally permits development settings, while production deployment evidence does not yet demonstrate HTTPS enforcement, secure/HTTP-only/SameSite session cookies, trusted-proxy configuration, session regeneration coverage for every login path, or production `APP_DEBUG=false` enforcement.
- **Minimal recommended fix:** Add deployment/configuration hardening and a parent-approved production verification checklist: TLS termination/enforcement, secure cookie flags, session lifetime/driver policy, proxy trust, and debug disabled. Do not place credentials in files, logs, or documentation.
- **Status:** open — excluded by authorization; PostgreSQL proof remains a parent/environment task

### Test/release evidence gaps

#### T-01 — PostgreSQL concurrency coverage missing

- **Requirement references:** ADR-003; PRD §8 quality/reliability; implementation plan §5 and §6.
- **Evidence/reproduction:** Existing evidence explicitly records SQLite in-memory execution and no established PostgreSQL availability.
- **Minimal recommended fix:** Run the bounded PostgreSQL concurrency/constraint suite in an approved environment and record exact command/output, or document the environment limitation and parent disposition.
- **Status:** resolved in authorized repair set; seeded identity gate and deterministic auth boundary retained

#### T-02 — Seeded-admin authentication coverage is incomplete

- **Requirement references:** PRD FR-AUTH-01–02 and §9; ADR-005.
- **Evidence/reproduction:** Tests cover protected access and invalid credentials, but evidence does not fully demonstrate safe environment-seeded credential creation, successful seeded-admin login, no credential disclosure, and absence of public registration/password-reset/customer-login/API paths.
- **Minimal recommended fix:** Add focused integration tests using test-only environment values that assert successful login and protected access without asserting or logging real credential values; assert excluded public account routes remain absent.
- **Status:** open — PostgreSQL-specific persistence/concurrency proof excluded

#### T-03 — Database constraints and persistence invariants are under-tested

- **Requirement references:** PRD FR-VEH-02, FR-CUST-03, FR-VEH-05; PRD §9; implementation plan database test matrix.
- **Evidence/reproduction:** SQLite feature tests do not establish PostgreSQL uniqueness/index/foreign-key behavior or concurrent duplicate prevention. Vehicle/customer no-delete and retention behavior also needs explicit persistence evidence.
- **Minimal recommended fix:** Add database-backed tests for normalized unique plates, email, phone, foreign keys, retained records, and competing duplicate writes. Keep production PostgreSQL settings unchanged.
- **Status:** resolved in authorized repair set; deterministic clock/lifecycle tests added

#### T-04 — Time-dependent tests do not cover the full injected-clock boundary

- **Requirement references:** PRD business rules 1–2 and 8–9; FR-VEH-06; FR-RENT-09; domain semantics; ADR-006.
- **Evidence/reproduction:** Existing domain tests cover selected date/pricing cases, but the review evidence does not establish a complete injected-WIB-clock suite for midnight transitions, activation, completion day-after-end, active status today, cancellation effective date, and date-aware availability.
- **Minimal recommended fix:** Add deterministic clock fixtures around WIB midnight/start/end/effective-end boundaries and assert read and write paths use the same clock.
- **Status:** open — view work excluded from this repair set

### Medium

#### M-01 — UI/customer edit workflow remains incomplete or insufficiently evidenced

- **Requirement references:** PRD FR-CUST-02, FR-CUST-05; accessibility/error requirements.
- **Evidence/reproduction:** Customer listing/persistence exists, but review evidence does not fully demonstrate an accessible customer edit form preserving rental links and preventing customer deletion/archive.
- **Minimal recommended fix:** Complete or verify the existing presentation flow with feature coverage for edit persistence, link retention, and absent delete/archive actions.
- **Status:** resolved in authorized repair set; backend filter/query contracts expanded without view edits

#### M-02 — Filter and status presentation coverage is incomplete

- **Requirement references:** PRD FR-VEH-03, FR-MASTER-01, FR-RENT-05, FR-DASH-01; accessibility requirements.
- **Evidence/reproduction:** Vehicle search/filter/pagination and rental/dashboard filters are implemented in separate slices, but evidence does not establish consistent brand/type/status/date facets and useful empty/error states across the full interface.
- **Minimal recommended fix:** Add focused presentation tests for each approved filter and preserve server-side query authority; do not add unapproved facets.
- **Status:** resolved in authorized repair set; known lifecycle mappings covered, broader UI mapping remains limited by view exclusion

#### M-03 — Indonesian error mapping is inconsistent across boundaries

- **Requirement references:** PRD §7; FR-VEH, FR-CUST, FR-RENT acceptance criteria.
- **Evidence/reproduction:** The customer invalid-phone update finding was fixed, but review evidence identifies inconsistent error mapping and possible generic errors across vehicle, rental, lifecycle, and availability flows.
- **Minimal recommended fix:** Map known domain/application rejection types to field/business-rule errors in Indonesian at the Presentation boundary; avoid leaking exception details or personal data.
- **Status:** resolved in authorized repair set; lifecycle, archive, and history timestamps use the injected clock

#### M-04 — Clock coherence across reads and writes is not demonstrated

- **Requirement references:** PRD WIB assumptions and business rules; architecture §21–23; ADR-006.
- **Evidence/reproduction:** An injected clock exists, but the review does not prove all status, lifecycle, dashboard, availability, and validation paths use it rather than direct system time.
- **Minimal recommended fix:** Bind one clock abstraction throughout Application use cases and query services, then add a test that fixes the clock and asserts coherent results across relevant reads/writes.
- **Status:** open

## Product-decision escalations (must not be invented)

The following are not implementation defects that reviewers may resolve by choosing a convenient behavior:

1. **`today_rental_total` inclusion semantics:** The PRD requires the metric but leaves its inclusion details unresolved. Parent/product authorization must define the qualifying states, dates, cancellation treatment, and price basis before implementation. Until then, retain the documented unresolved state rather than inventing a total.
2. **Booked cancellation before start reuse:** The PRD/domain artifact records that cancelled booked rentals free their future range but explicitly leaves the immediate reuse interpretation requiring clarification. Parent/product authorization must decide the exact date reuse behavior before implementation. Do not infer it from the effective-end rule.

## Authorized repair resolution

The parent authorized backend/test/security repair set was applied without editing `resources/views/**`, CSS, JS, package assets, or dashboard metric semantics. Lifecycle advancement, completion guards, archived edit rejection, derived/read contracts, query filters, seeded-admin identity enforcement, injected-clock timestamps, session/TLS placeholders, and deterministic tests were addressed where feasible. `today_rental_total` remains `null`; cancellation-before-start reuse remains undecided; PostgreSQL concurrency proof was not attempted.

## Fixable defects versus escalations

- **Fixable defects:** Authorized backend portions of C-01 and H-01–H-08, T-02, T-04, and M-02–M-04 were addressed. H-03/M-01 remain limited by the explicit no-view-edit scope. T-01/T-03 remain open because PostgreSQL proof was excluded.
- **Product escalations:** C-02 and the booked-cancellation-before-start reuse question require an explicit product decision. They must remain open and must not be silently converted into implementation rules.

## Known constraints

- The current recorded automated test environment uses SQLite in-memory; PostgreSQL concurrency and PostgreSQL-specific constraint behavior remain unverified.
- This review does not claim browser, accessibility, production TLS, deployment, or seeded-production-credential evidence beyond the supplied records.
- Review findings are based on the parent-supplied independent/security/test reviewer context and repository artifacts listed above; this document does not replace parent validation or human acceptance.
- Prompt 09 is documentation-only. No application behavior is changed by this artifact.

## Phase 9 handoff

- **Phase / owner:** 9 — Independent review / independent reviewer, security reviewer, and test reviewer
- **Inputs:** Sources listed in “Review scope and inputs”; parent-supplied review findings and evidence
- **Output:** `docs/reviews/prompt-09-independent-review.md`
- **Evidence:** Findings C-01–C-02, H-01–H-08, T-01–T-04, and M-01–M-04 with severity, requirement references, reproduction/evidence, minimal recommended fixes, status, constraints, and explicit product escalations.
- **Open questions:** `today_rental_total` inclusion semantics and booked-cancellation-before-start reuse require parent/product decisions; PostgreSQL and production-hardening evidence remain outstanding.
- **Gate decision:** **HOLD** — authorized repair applied; excluded product decisions and PostgreSQL/environment evidence remain pending parent disposition.
- **Changed paths:** `docs/reviews/prompt-09-independent-review.md`, `docs/README.md`
- **Verification:** Focused and full test commands, Composer validation, route listing, PHP lint, and diff check are recorded in the AI log; migration status was attempted safely but could not run because the configured SQLite file was absent.
- **Approver / timestamp:** Parent/orchestrator / pending; 24 August 2026 (WIB)
