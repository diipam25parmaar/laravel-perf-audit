<?php
namespace PerfAudit\Report;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class Analyzer
{
    protected $db;
    protected $log;
    protected $connectionDriver;

    public function __construct($db, $log)
    {
        $this->db = $db;
        $this->log = $log;
        $this->connectionDriver = $this->db->getDriverName();
    }

    // analyze all capture files in folder and return suggestions aggregated
    public function analyzeFolder($folder = null)
    {
        $folder = $folder ?: storage_path('logs/perf-audit');
        if (!is_dir($folder)) return [];

        $files = glob(rtrim($folder, DIRECTORY_SEPARATOR).'/*.json');
        $queries = [];
        foreach ($files as $f) {
            $data = json_decode(file_get_contents($f), true);
            if (!$data || empty($data['sql'])) continue;
            $sql = $data['sql'];
            $time = $data['time_ms'] ?? 0;
            $queries[] = ['sql' => $sql, 'time' => $time];
        }

        // group similar queries (basic normalized SQL)
        $groups = $this->groupQueries($queries);

        $suggestions = [];
        foreach ($groups as $norm => $group) {
            // extract candidate columns from sample SQL
            $sample = $group['sample_sql'];
            $columns = $this->extractColumnsFromSql($sample);
            foreach ($columns as $col) {
                if (strpos($col, '.') === false) continue;
                list($table, $column) = explode('.', $col, 2);
                // skip if already indexed
                if ($this->isColumnIndexed($table, $column)) continue;
                $suggestions[] = [
                    'type' => 'index',
                    'table' => $table,
                    'columns' => [$column],
                    'frequency' => $group['count'],
                    'avg_time_ms' => $group['avg_time'],
                    'reason' => 'Used in WHERE/ORDER BY of frequent slow queries'
                ];
            }
        }

        // rank by frequency * avg_time
        usort($suggestions, function($a, $b){
            $va = ($a['frequency'] * ($a['avg_time_ms'] ?? 1));
            $vb = ($b['frequency'] * ($b['avg_time_ms'] ?? 1));
            return $vb <=> $va;
        });

        return $suggestions;
    }

    protected function groupQueries($queries)
    {
        $groups = [];
        foreach ($queries as $q) {
            $norm = $this->normalizeSql($q['sql']);
            if (!isset($groups[$norm])) {
                $groups[$norm] = ['count'=>0,'total_time'=>0,'sample_sql'=>$q['sql']];
            }
            $groups[$norm]['count'] += 1;
            $groups[$norm]['total_time'] += ($q['time'] ?? 0);
        }
        foreach ($groups as $k => $g) {
            $groups[$k]['avg_time'] = $g['total_time'] / max(1, $g['count']);
        }
        return $groups;
    }

    protected function normalizeSql($sql)
    {
        // remove bindings like numbers and strings, collapse whitespace, remove literals
        $s = preg_replace('/\'.'".*?"'.'|\'.'\'.'*?\'.'\'/s', '?', $sql);
        $s = preg_replace('/\d+/', '?', $s);
        $s = preg_replace('/\s+/', ' ', $s);
        $s = strtolower(trim($s));
        // keep only SELECT ... FROM ... WHERE/ORDER BY portion
        if (strpos($s, ' where ') !== false) {
            $s = substr($s, 0, strpos($s, ' where ')+7);
        }
        return $s;
    }

    protected function extractColumnsFromSql($sql)
    {
        $cols = [];
        // match patterns like `table`.`col` or table.col
        preg_match_all('/([\w`]+)\.([\w`]+)/', $sql, $m, PREG_SET_ORDER);
        foreach ($m as $mm) {
            $t = trim($mm[1], '`');
            $c = trim($mm[2], '`');
            $cols[] = $t.'.'.$c;
        }
        // also try WHERE (\w+) = ? (no table) - skip as ambiguous
        return array_values(array_unique($cols));
    }

    protected function isColumnIndexed($table, $column)
    {
        try {
            $driver = $this->connectionDriver;
            $conn = DB::connection();
            if (in_array($driver, ['mysql', 'mysqli'])) {
                $dbName = $conn->getDatabaseName();
                $sql = "SELECT COUNT(1) as cnt FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?";
                $res = $conn->select($sql, [$dbName, $table, $column]);
                return !empty($res) && ($res[0]->cnt ?? $res[0]['cnt'] ?? 0) > 0;
            } elseif (in_array($driver, ['pgsql','postgres'])) {
                $sql = "SELECT COUNT(1) as cnt FROM pg_indexes WHERE tablename = ?";
                $res = $conn->select($sql, [$table]);
                if (empty($res)) return false;
                // simple check: see if column name appears in indexdef
                foreach ($res as $r) {
                    $idxdef = is_object($r) ? $r->indexdef ?? '' : $r['indexdef'] ?? '';
                    if (stripos($idxdef, '($column)') !== false) continue;
                    if (stripos($idxdef, $column) !== false) return true;
                }
                return false;
            } else {
                // fallback - assume not indexed
                return false;
            }
        } catch (\Exception $e) {
            // if introspection fails, assume not indexed so we can suggest
            return false;
        }
    }
}
