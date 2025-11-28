# Usage (expanded)

## Capture mode (local/dev)
1. Start capture:
   ```
   php artisan perf-audit:run
   ```
   Keep it running while you exercise the application flows (e.g., API requests). Captures are saved to `storage/logs/perf-audit/` as JSON files.

## Analyze captured files (aggregate)
   ```
   php artisan perf-audit:analyze --folder=storage/logs/perf-audit --out=storage/app/perf-audit-suggestions.json
   ```
   This aggregates all captures, ranks suggestions, and skips columns that already have indexes.

## Generate migrations from suggestions
   ```
   php artisan perf-audit:make-migrations --file=storage/app/perf-audit-suggestions.json
   ```
   This command will create draft migrations in your host app `database/migrations/` directory. Review the migrations before running them.

## CI integration
Add the included GitHub Action to run `php artisan perf-audit:analyze` against a sample set of captures (or CI-generated samples) to detect regressions in PRs.
