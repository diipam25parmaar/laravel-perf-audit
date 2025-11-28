<?php
return [
    'capture' => [
        'enabled' => env('PERF_AUDIT_CAPTURE', true),
        'min_duration_ms' => env('PERF_AUDIT_MIN_DURATION_MS', 15),
        'sample_rate' => env('PERF_AUDIT_SAMPLE_RATE', 1), // 1 == capture all
    ],
    'report' => [
        'output_path' => storage_path('logs/perf-audit'),
    ],
];
