# Installation

1. Place this package in `packages/your-vendor/laravel-perf-audit`.
2. In your main project's `composer.json` add:
   ```json
   {
     "repositories": [
       {
         "type": "path",
         "url": "packages/your-vendor/laravel-perf-audit"
       }
     ]
   }
   ```
3. Run:
   ```
   composer require your-vendor/laravel-perf-audit:@dev
   php artisan vendor:publish --provider="PerfAudit\PerfAuditServiceProvider" --tag=config
   ```
4. Configure `config/perf-audit.php` and set `PERF_AUDIT_CAPTURE=true` locally.
