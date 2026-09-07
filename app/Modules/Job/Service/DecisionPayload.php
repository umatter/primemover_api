<?php

namespace App\Modules\Job\Service;

use App\Modules\Report\Model\Report;

/**
 * One decision-function response, as it was recorded for a job.
 *
 * The stored object is what the runner's LambdaCaller wrote, not what the
 * decision function returned:
 *
 *     {"success": true, "statusCode": 200, "functionError": null,
 *      "payload": "{\"xpath\": \"\", \"degraded\": true, ...}"}
 *
 * success and statusCode describe the HTTP CALL, which is 200 even when every
 * requested filter failed. Reading them as the decision's own verdict reports a
 * degraded, empty-xpath decision as "ok" -- exactly the silent success the
 * degradation report exists to prevent. So the two are kept apart here:
 * $payload is the decision, $invocation is the call that carried it.
 */
final class DecisionPayload
{
    /**
     * @param array<string, mixed> $payload    the decision function's own response
     * @param array<string, mixed> $invocation the runner's envelope around it (never a verdict)
     */
    public function __construct(
        public readonly Report $report,
        public readonly string $entry,
        public readonly array $payload,
        public readonly array $invocation,
    ) {}

    public function verdict(): DecisionVerdict
    {
        return DecisionVerdict::fromPayload($this->payload);
    }
}
