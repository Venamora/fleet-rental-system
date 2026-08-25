# Deterministic Development Workflow

This is the project’s gate contract. The approved PRD in `docs/PRD.md` is the product baseline; `PRD` is the authoritative assessment and business-rule source. Neither may be silently reinterpreted. The parent orchestrator owns validation and gate decisions; the named owner produces the artifact.

## Non-negotiable rules

- Work only in the current approved scope. No speculative features, refactors, technology choices, schema, routes, commands, or documentation invented to fill a gap.
- Keep the layers below distinct. A later artifact may propose or implement only what an earlier approved artifact authorizes.
- Every handoff records: input references, output path/content, owner, evidence, unresolved questions, and gate decision (`pass`, `hold`, or `escalate`).
- A failed gate blocks downstream work. Escalate ambiguity, conflicting requirements, missing approval, or an untestable claim to the parent orchestrator; do not guess.
- Every AI-assisted action must be logged in `docs/AI-USAGE.md` (prompt/task, files read or changed, result, human disposition). No unlogged AI output is evidence.
- This bootstrap task writes workflow guidance only. It explicitly does **not** implement application code or architecture, create ADRs, schemas, routes, commands, or alter `docs/PRD.md` or `docs/AI-USAGE.md`.

## Ordered phases and gates

| # | Layer | Input | Output | Owner | Gate evidence |
|---|---|---|---|---|---|
| 1 | Requirements | `PRD`, approved `docs/PRD.md` | Requirements baseline: actors, scope, acceptance criteria, assumptions, questions | Requirements owner | Parent confirms traceability and scope; unresolved items are escalated |
| 2 | Domain semantics | Passed requirements baseline | Domain glossary, invariants, examples, date/duration/price semantics | Domain owner | Parent approves semantics before any architecture work |
| 3 | Architecture | Approved requirements and domain semantics | Architecture options, trade-offs, and approved architecture record | Architecture owner | Explicit parent approval; no implementation before this gate |
| 4 | ADR | Approved architecture and unresolved significant decisions | Numbered ADRs only for approved decisions | Decision owner | Parent accepts each ADR or records rejection; never create speculative ADRs |
| 5 | Implementation plan | Approved requirements, domain, architecture, ADRs | Ordered work slices, touched paths, dependencies, risks, rollback | Plan owner | Parent approves bounded plan and scope |
| 6 | Test design | Approved baseline plus implementation plan | Test matrix mapped to acceptance criteria and domain invariants | Test owner | Independent test review confirms observable coverage |
| 7 | Implementation | Passed plan and test design | Minimal application changes in approved paths | Implementer | Diff is traceable to a plan slice; no unapproved behavior |
| 8 | Tests | Implemented slice and test design | Test results and reproducible command/output evidence | Test owner | All required checks pass, or failure is escalated |
| 9 | Independent review | Diff, requirements, domain, ADRs, test evidence | Findings classified by severity and scope | Independent reviewer | Reviewer did not author the change; findings acknowledged |
| 10 | Fixes | Review findings and failing evidence | Targeted fixes plus updated tests/evidence | Implementer | Reviewer or parent confirms each finding is resolved |
| 11 | Verification | Final diff, tests, repository scripts/config | Re-run evidence, status, and known limitations | Validation owner: parent | Parent records exact commands; never claim unrun checks |
| 12 | Docs | Verified behavior and approved decisions | Updated required README/AI transparency documentation | Documentation owner | Docs match implementation and contain no invented claims |
| 13 | Human review | All prior artifacts and evidence | Final acceptance, rejection, or explicit follow-up list | Human/parent approver | Delivery occurs only after explicit human decision |

## Handoff record

Use this compact record for every phase: `Phase / owner / inputs / output / evidence / open questions / gate decision / approver / timestamp`. Link evidence to actual files, diffs, and commands. If an input is unavailable, stop at `hold`; if requirements conflict, stop at `escalate`. Approval is not implied by producing a file.
