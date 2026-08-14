<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogSlowAdminRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('performance.trace_admin_requests', false)) {
            return $next($request);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $startedAt = hrtime(true);

        try {
            return $next($request);
        } finally {
            $elapsedMs = (hrtime(true) - $startedAt) / 1_000_000;
            $queries = DB::getQueryLog();
            DB::disableQueryLog();
            $threshold = max(1, (int) config('performance.slow_request_ms', 750));

            if ($elapsedMs >= $threshold) {
                Log::warning('admin.performance.slow_request', [
                    'method' => $request->method(),
                    'path' => $request->path(),
                    'user_id' => $request->user()?->getKey(),
                    'elapsed_ms' => round($elapsedMs, 1),
                    'query_count' => count($queries),
                    'query_ms' => round((float) collect($queries)->sum('time'), 1),
                ]);
            }
        }
    }
}
