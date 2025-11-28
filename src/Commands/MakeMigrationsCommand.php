<?php
namespace PerfAudit\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeMigrationsCommand extends Command
{
    protected $signature = 'perf-audit:make-migrations {--file=} {--pretend}';
    protected $description = 'Create draft migration files from analyzer suggestions (writes to host app database/migrations)';

    public function handle()
    {
        $this->info('Generating migration stubs from perf-audit suggestions...');
        $file = $this->option('file') ?: storage_path('app/perf-audit-suggestions.json');
        if (!file_exists($file)) {
            $this->error('Suggestions file not found: '.$file);
            return 1;
        }

        $json = json_decode(file_get_contents($file), true);
        if (!$json) {
            $this->error('Invalid suggestions file.');
            return 1;
        }

        $fs = new Filesystem();
        $migrationsPath = base_path('database/migrations');
        if (!is_dir($migrationsPath)) mkdir($migrationsPath, 0755, true);

        $created = [];
        foreach ($json as $s) {
            if (($s['type'] ?? '') !== 'index') continue;
            $table = $s['table'];
            $cols = $s['columns'];
            $name = 'add_index_'.implode('_', array_merge([$table], $cols)).'_perf_audit';
            $timestamp = date('Y_m_d_His').Str::random(4);
            $filename = $timestamp.'_'.Str::slug($name).'.php';
            $full = $migrationsPath.DIRECTORY_SEPARATOR.$filename;
            $migrationClass = 'AddIndex'.Str::studly($table).Str::studly(implode('_',$cols)).'PerfAudit';
            $colsPhp = var_export($cols, true);
            $stub = <<<PHP
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (Schema::hasTable('$table')) {
            Schema::table('$table', function (Blueprint \$table) {
                \$table->index($colsPhp, null);
            });
        }
    }
    public function down()
    {
        if (Schema::hasTable('$table')) {
            Schema::table('$table', function (Blueprint \$table) {
                \$table->dropIndex([\"$colsPhp\"]);
            });
        }
    }
};
PHP;
            if ($this->option('pretend')) {
                $this->line("[pretend] would create: $full");
            } else {
                file_put_contents($full, $stub);
                $created[] = $full;
                $this->line('Created: '.$full);
            }
        }

        $this->info('Done. '.count($created).' migrations created.');
        return 0;
    }
}
