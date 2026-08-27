<?php

namespace App\Modules\Queue\Http\Request;

class RunnerQueueUpdateRequest extends UpdateQueueRequest
{
    /**
     * Largest status_message we accept, in BYTES -- status_message is a MySQL
     * TEXT column (65535 bytes, not characters). See RunnerJobUpdateRequest for
     * the full reasoning; the queue endpoint is hardened the same way because
     * the runner posts joblist diagnostics through it.
     */
    public const STATUS_MESSAGE_MAX = 60000;

    /**
     * The model is $guarded = [], so whatever survives validation here is
     * mass-assigned; every field used to be a bare ['nullable'].
     *
     * No in: whitelist on status_code and no date rule on the timestamps: a 422
     * is swallowed by the runner (JobService::updateJoblistState logs and
     * returns false), which would leave the queue stuck mid-run.
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'active' => ['nullable', 'boolean'],
            'new' => ['nullable', 'boolean'],
            'processed' => ['nullable', 'boolean'],
            'processing' => ['nullable', 'boolean'],
            'reviewed' => ['nullable', 'boolean'],
            'status_code' => ['nullable', 'string', 'max:255'],
            'status_message' => ['nullable', 'string', 'max:'.self::STATUS_MESSAGE_MAX],
            'order' => ['nullable', 'integer'],
            // start_at/started_at/finished_at are varchar(255) columns; the
            // runner writes an ISO-8601 string into them verbatim.
            'start_at' => ['nullable', 'string', 'max:255'],
            'updated_at' => ['nullable', 'string', 'max:255'],
            'created_at' => ['nullable', 'string', 'max:255'],
            'started_at' => ['nullable', 'string', 'max:255'],
            'finished_at' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Truncate an over-long diagnostic rather than reject the update; cut on
     * bytes so the result fits the TEXT column.
     */
    protected function prepareForValidation(): void
    {
        $message = $this->input('status_message');

        if (is_string($message) && mb_strlen($message, '8bit') > self::STATUS_MESSAGE_MAX) {
            $this->merge([
                'status_message' => mb_strcut($message, 0, self::STATUS_MESSAGE_MAX - 12).' [truncated]',
            ]);
        }
    }
}
