# Architecture

- Capture: QueryListener writes JSON captures for slow queries.
- Analyze: Analyzer reads captures and heuristically suggests index candidates.
- Developer: human reviews suggestions and converts to migration files (a helper command could scaffold migrations).
- CI: Run lightweight analyzer on PRs to surface regressions.
