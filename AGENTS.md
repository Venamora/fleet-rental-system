# Agent instructions

## Before any task

- Read `PRD`; it is the authoritative assessment specification and business-rule source.
- Inspect the current manifests, scripts, tests, and documentation before editing. `docs/PRD.md` is the approved product baseline, and `docs/DEVELOPMENT-WORKFLOW.md` is the controlling workflow; do not assume a stack or runnable command.
- Do not build application code during agent-environment/bootstrap work unless the user explicitly changes the task.

## Route work to the right source

- Requirements, scope, actors, acceptance criteria, or out-of-scope questions → `docs/PRD.md`, with `PRD` retained as the authoritative assessment and business-rule source.
- Vehicle CRUD or rental-form work → `PRD`, then the relevant implementation and tests.
- Rental duration, the `> 7`-day 10% discount, date-boundary semantics, or overlap rejection → `PRD`, then the domain implementation and focused tests.
- Architecture, folder boundaries, setup, usage, and verified commands → `README.md` once created, plus the actual manifests/configuration; never invent commands.
- Significant technical choices → the relevant `docs/ADR/*.md` record once that documentation structure exists.
- Test strategy and verification evidence → the project test configuration and `README.md`; verify using the repository’s actual scripts.
- UI/UX tasks → the project UI/UX skill/documentation once created, then the affected UI code and tests.
- Security or code-review tasks → the project security/review guidance once created, then inspect without changing scope.
- AI-assisted work → preserve the required transparency in `README.md` and `docs/AI-USAGE.md` once created.

## Workflow constraints

- Keep requirements/specification, architecture, implementation, verification, and review distinct; implementation must not silently redefine requirements.
- Change only files required by the task. Do not create speculative documentation, ADRs, skills, agents, MCP servers, or application features.
- Keep generic workflow guidance in global OpenCode configuration/skills; keep Fleet & Rental rules in this repository.
- Before declaring a task complete, run the applicable verified checks and update affected documentation. Do not claim success without evidence.
- Do not commit, push, or perform destructive operations unless explicitly requested.
