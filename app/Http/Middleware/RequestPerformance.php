<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Records aggregate timing for slow requests without logging SQL,
 * bindings, request bodies, cookies, or authentication information.
 */
class RequestPerformance
{
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = hrtime(true);
        $request->attributes->set('_performance_db_queries', 0);
        $request->attributes->set('_performance_db_ms', 0.0);
        $response = null;

        try {
            $response = $next($request);

            return $response;
        } catch (Throwable $exception) {
            throw $exception;
        } finally {
            $durationMs = (hrtime(true) - $startedAt) / 1_000_000;
            $queryCount = (int) $request->attributes->get('_performance_db_queries', 0);
            $queryDurationMs = (float) $request->attributes->get('_performance_db_ms', 0.0);

            // Keep routine-request noise low while preserving evidence for
            // the multi-second waits seen in the browser Network panel.
            if ($durationMs >= 500 || $queryDurationMs >= 300) {
                Log::info('HTTP performance diagnostic', [
                    'method' => $request->method(),
                    'route' => $request->route()?->uri() ?? $request->path(),
                    'status' => $response?->getStatusCode() ?? 500,
                    'duration_ms' => round($durationMs, 2),
                    'db_query_count' => $queryCount,
                    'db_query_ms' => round($queryDurationMs, 2),
                ]);
            }
        }
    }
}
