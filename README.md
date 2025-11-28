# laravel-perf-audit

**Purpose:** Continuously detect Laravel DB performance problems (N+1, slow/select-heavy queries, missing/unused indexes), produce human-reviewed, PR-ready index migrations and a CI check to prevent regressions.

## Highlights
- Capture slow queries as JSON
- Aggregate captures and suggest index candidates (avoids already indexed columns)
- Generate migration stubs automatically
- CI integration example to run analyzer on PRs

## Quick install (local development)
1. Place this package in `packages/your-vendor/laravel-perf-audit` or require via composer (when published).
2. Add to your project's `composer.json` (path repository)
3. `composer require your-vendor/laravel-perf-audit:@dev`
4. `php artisan vendor:publish --provider="PerfAudit\PerfAuditServiceProvider" --tag=config`
5. Capture and analyze (see docs/USAGE.md)

## Commands
- `php artisan perf-audit:run` — capture slow queries live
- `php artisan perf-audit:analyze` — aggregate captures and create suggestions
- `php artisan perf-audit:make-migrations` — scaffold migration files from suggestions

See docs for detailed examples and CI integration.
