<?php
namespace PerfAudit\Commands;

use Illuminate\Console\Command;
use PerfAudit\Report\Analyzer;

class AnalyzeCommand extends Command
{
    protected $signature = 'perf-audit:analyze {--folder=} {--out=}';
    protected $description = 'Analyze captured query logs and generate suggested index migrations (draft SQL/migration)';

    public function handle()
    {
        $this->info('Analyzing perf-audit captures...');
        $folder = $this->option('folder') ?: storage_path('logs/perf-audit');
        $analyzer = $this->getLaravel()->make('perfaudit.analyzer');
        $suggestions = $analyzer->analyzeFolder($folder);
        $out = $this->option('out') ?: storage_path('app/perf-audit-suggestions.json');
        file_put_contents($out, json_encode($suggestions, JSON_PRETTY_PRINT));
        $this->info("Suggestions written to: {$out}");
        if (!empty($suggestions)) {
            $this->table(['type','table','columns','frequency','avg_ms','reason'], array_map(function($s){
                return [$s['type'] ?? 'index', $s['table'], implode(',', $s['columns']), $s['frequency'] ?? 0, $s['avg_time_ms'] ?? 0, $s['reason']];
            }, $suggestions));
        } else {
            $this->info('No suggestions found.');
        }
    }
}
