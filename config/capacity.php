<?php

return [
    'database_budget_bytes' => 500000000,
    'database_warning_percent' => 80,
    'disk_min_free_bytes' => 10 * 1024 * 1024 * 1024,
    'queue_warning_jobs' => 1000,
    'queue_warning_age_seconds' => 900,
    'monitor_enabled' => env('CAPACITY_MONITOR_ENABLED', false),
];
