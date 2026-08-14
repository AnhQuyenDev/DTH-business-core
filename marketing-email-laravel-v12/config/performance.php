<?php

return [
    /*
    | A short snapshot prevents repeated Livewire/dashboard refreshes from
    | rebuilding the same read model. Set to 0 while profiling uncached SQL.
    */
    'dashboard_cache_seconds' => (int) env('DASHBOARD_CACHE_SECONDS', 45),

    // Diagnostic only. When enabled, slow admin requests are written to the
    // normal Laravel log with duration and query totals, without SQL values.
    'trace_admin_requests' => (bool) env('PERFORMANCE_TRACE_ENABLED', false),
    'slow_request_ms' => (int) env('PERFORMANCE_SLOW_REQUEST_MS', 750),
];
