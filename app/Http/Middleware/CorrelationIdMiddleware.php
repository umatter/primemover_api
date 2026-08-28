<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Correlation ID Middleware
 *
 * Generates and propagates correlation IDs for request tracing across components.
 *
 * Correlation ID format: exp-{experiment_id}|cr-{crawler_id}|q-{queue_id}|j-{job_id}
 *
 * Headers:
 *   - X-Correlation-Id: Full correlation ID
 *   - X-Request-Id: Unique request ID (UUID)
 *
 * Log Context:
 *   - correlation_id: Full correlation ID
 *   - request_id: Unique request ID
 *   - experiment_id: Experiment ID (if available)
 *   - crawler_id: Crawler ID (if available)
 *   - queue_id: Queue ID (if available)
 *   - job_id: Job ID (if available)
 */
class CorrelationIdMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  Closure                  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Generate or extract request ID
        $requestId = $request->header('X-Request-Id') ?? Str::uuid()->toString();

        // Extract correlation ID from header or build from request
        $correlationId = $request->header('X-Correlation-Id');

        if ( ! $correlationId) {
            $correlationId = $this->buildCorrelationId($request);
        }

        // Parse correlation ID components
        $context = $this->parseCorrelationId($correlationId);
        $context['request_id'] = $requestId;
        $context['correlation_id'] = $correlationId ?: null;

        // Enrich from route parameters if available
        $context = $this->enrichFromRoute($request, $context);

        // Add to log context
        Log::shareContext($context);

        // Store in request for later use
        $request->attributes->set('correlation_id', $correlationId);
        $request->attributes->set('request_id', $requestId);
        $request->attributes->set('correlation_context', $context);

        // Process request
        $response = $next($request);

        // Add correlation headers to response
        $response->headers->set('X-Request-Id', $requestId);

        if ($correlationId) {
            $response->headers->set('X-Correlation-Id', $correlationId);
        }

        // Build final correlation ID including any IDs from response
        $finalCorrelationId = $this->buildFinalCorrelationId($context);
        if ($finalCorrelationId) {
            $response->headers->set('X-Correlation-Id', $finalCorrelationId);
        }

        return $response;
    }

    /**
     * Build correlation ID from request parameters.
     *
     * @param  \Illuminate\Http\Request $request
     * @return string|null
     */
    protected function buildCorrelationId(Request $request): ?string
    {
        $parts = [];

        // Check route parameters
        $experimentId = $request->route('experiment') ?? $request->route('experiment_id');
        $crawlerId = $request->route('crawler') ?? $request->route('crawler_id');
        $queueId = $request->route('queue') ?? $request->route('queue_id') ?? $request->route('joblist') ?? $request->route('joblist_id');
        $jobId = $request->route('job') ?? $request->route('job_id');

        // Check query parameters
        $experimentId = $experimentId ?? $request->query('experiment_id');
        $crawlerId = $crawlerId ?? $request->query('crawler_id');
        $queueId = $queueId ?? $request->query('queue_id') ?? $request->query('joblist_id');
        $jobId = $jobId ?? $request->query('job_id');

        // Check request body for JSON
        if ($request->isJson()) {
            $json = $request->json();
            $experimentId = $experimentId ?? $json->get('experiment_id');
            $crawlerId = $crawlerId ?? $json->get('crawler_id');
            $queueId = $queueId ?? $json->get('queue_id') ?? $json->get('joblist_id');
            $jobId = $jobId ?? $json->get('job_id');
        }

        // Build correlation ID parts
        if ($experimentId) {
            $parts[] = "exp-{$experimentId}";
        }
        if ($crawlerId) {
            $parts[] = "cr-{$crawlerId}";
        }
        if ($queueId) {
            $parts[] = "q-{$queueId}";
        }
        if ($jobId) {
            $parts[] = "j-{$jobId}";
        }

        return count($parts) > 0 ? implode('|', $parts) : null;
    }

    /**
     * Parse correlation ID into components.
     *
     * @param  string|null $correlationId
     * @return array
     */
    protected function parseCorrelationId(?string $correlationId): array
    {
        $context = [
            'experiment_id' => null,
            'crawler_id' => null,
            'queue_id' => null,
            'job_id' => null,
        ];

        if ( ! $correlationId) {
            return $context;
        }

        // Parse exp-N
        if (preg_match('/exp-(\d+)/', $correlationId, $matches)) {
            $context['experiment_id'] = (int) $matches[1];
        }

        // Parse cr-N
        if (preg_match('/cr-(\d+)/', $correlationId, $matches)) {
            $context['crawler_id'] = (int) $matches[1];
        }

        // Parse q-N or jl-N
        if (preg_match('/(?:q|jl)-(\d+)/', $correlationId, $matches)) {
            $context['queue_id'] = (int) $matches[1];
        }

        // Parse j-N
        if (preg_match('/j-(\d+)/', $correlationId, $matches)) {
            $context['job_id'] = (int) $matches[1];
        }

        return $context;
    }

    /**
     * Enrich context from route parameters.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  array                    $context
     * @return array
     */
    protected function enrichFromRoute(Request $request, array $context): array
    {
        // Get IDs from route parameters (model binding)
        $routeParams = $request->route()?->parameters() ?? [];

        foreach ($routeParams as $key => $value) {
            // Handle model instances
            if (is_object($value) && method_exists($value, 'getKey')) {
                $id = $value->getKey();

                if (str_contains(mb_strtolower($key), 'experiment')) {
                    $context['experiment_id'] = $context['experiment_id'] ?? $id;
                } elseif (str_contains(mb_strtolower($key), 'crawler')) {
                    $context['crawler_id'] = $context['crawler_id'] ?? $id;
                } elseif (str_contains(mb_strtolower($key), 'queue') || str_contains(mb_strtolower($key), 'joblist')) {
                    $context['queue_id'] = $context['queue_id'] ?? $id;
                } elseif (str_contains(mb_strtolower($key), 'job')) {
                    $context['job_id'] = $context['job_id'] ?? $id;
                }
            } elseif (is_numeric($value)) {
                // Handle raw IDs
                if (str_contains(mb_strtolower($key), 'experiment')) {
                    $context['experiment_id'] = $context['experiment_id'] ?? (int) $value;
                } elseif (str_contains(mb_strtolower($key), 'crawler')) {
                    $context['crawler_id'] = $context['crawler_id'] ?? (int) $value;
                } elseif (str_contains(mb_strtolower($key), 'queue') || str_contains(mb_strtolower($key), 'joblist')) {
                    $context['queue_id'] = $context['queue_id'] ?? (int) $value;
                } elseif (str_contains(mb_strtolower($key), 'job')) {
                    $context['job_id'] = $context['job_id'] ?? (int) $value;
                }
            }
        }

        return $context;
    }

    /**
     * Build final correlation ID from context.
     *
     * @param  array       $context
     * @return string|null
     */
    protected function buildFinalCorrelationId(array $context): ?string
    {
        $parts = [];

        if ( ! empty($context['experiment_id'])) {
            $parts[] = "exp-{$context['experiment_id']}";
        }
        if ( ! empty($context['crawler_id'])) {
            $parts[] = "cr-{$context['crawler_id']}";
        }
        if ( ! empty($context['queue_id'])) {
            $parts[] = "q-{$context['queue_id']}";
        }
        if ( ! empty($context['job_id'])) {
            $parts[] = "j-{$context['job_id']}";
        }

        return count($parts) > 0 ? implode('|', $parts) : null;
    }
}
