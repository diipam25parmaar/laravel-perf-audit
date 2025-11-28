<?php
namespace PerfAudit;

use Illuminate\Support\ServiceProvider;
use PerfAudit\Commands\RunAuditCommand;
use PerfAudit\Commands\AnalyzeCommand;
use PerfAudit\Commands\MakeMigrationsCommand;

class PerfAuditServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/perf-audit.php' => config_path('perf-audit.php'),
            ], 'config');

            $this->commands([
                RunAuditCommand::class,
                AnalyzeCommand::class,
                MakeMigrationsCommand::class,
            ]);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/perf-audit.php', 'perf-audit');

        $this->app->singleton('perfaudit.analyzer', function($app){
            return new Report\Analyzer($app['db'], $app['log']);
        });
    }
}
