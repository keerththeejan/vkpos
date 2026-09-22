<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PerformanceDebug
{
    /**
     * Add non-sensitive performance headers for local/debug use only.
     */
    public function handle(Request $request, Closure $next)
    {
        if (! $this->isEnabled($request)) {
            return $next($request);
        }

        $startedAt = microtime(true);
        $queryCount = 0;
        $sqlMs = 0.0;

        DB::listen(function ($query) use (&$queryCount, &$sqlMs) {
            $queryCount++;
            $sqlMs += $query->time;
        });

        $response = $next($request);

        $payload = [
            'execution_ms' => round((microtime(true) - $startedAt) * 1000, 1),
            'sql_queries' => $queryCount,
            'sql_ms' => round($sqlMs, 1),
            'memory_mb' => round(memory_get_usage(true) / 1048576, 2),
            'peak_memory_mb' => round(memory_get_peak_usage(true) / 1048576, 2),
        ];

        $response->headers->set('X-VKPOS-Perf', json_encode($payload));

        if ($request->boolean('vkpos_perf') && ! $request->ajax()) {
            $content = $response->getContent();
            if (is_string($content) && str_contains($content, '</body>')) {
                $comment = "\n<!-- VKPOS PERFORMANCE DEBUG\n"
                    ."Execution Time: {$payload['execution_ms']} ms\n"
                    ."SQL Queries: {$payload['sql_queries']}\n"
                    ."SQL Time: {$payload['sql_ms']} ms\n"
                    ."Memory Usage: {$payload['memory_mb']} MB\n"
                    ."Peak Memory: {$payload['peak_memory_mb']} MB\n"
                    ."-->\n";
                $response->setContent(str_replace('</body>', $comment.'</body>', $content));
            }
        }

        return $response;
    }

    protected function isEnabled(Request $request): bool
    {
        if (filter_var(config('app.vkpos_perf_debug', false), FILTER_VALIDATE_BOOLEAN)) {
            return true;
        }

        $remote = $request->ip();
        $isLocal = in_array($remote, ['127.0.0.1', '::1'], true);

        return $isLocal && $request->boolean('vkpos_perf');
    }
}
