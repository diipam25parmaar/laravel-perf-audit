<?php
namespace PerfAudit\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PerfAudit\Listeners\QueryListener;

class RunAuditCommand extends Command
{
    protected $signature = 'perf-audit:run {--session=default}';
    protected $description = 'Run a lightweight capture of DB queries in the current environment (for local/dev)';

    public function handle()
    {
        $this->info('Starting perf-audit capture session...');
        $session = $this->option('session');
        $listener = new QueryListener(config('perf-audit.capture.min_duration_ms'));
        DB::listen([$listener, 'handle']);
        $this->info('Listening for queries. Run your app flows now (Ctrl+C to stop). Captures saved to storage/logs/perf-audit.');
        // keep process alive until killed
        while (true) {
            sleep(2);
        }
    }
}
