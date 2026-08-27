<?php

namespace App\Modules\Job\Http\Request;

class RunnerJobUpdateRequest extends UpdateJobRequest
{
    /**
     * Largest status_message we accept, in BYTES.
     *
     * status_message is a MySQL TEXT column: 65535 bytes, not characters. The
     * runner started attaching a real diagnostic to it on 2026-08-27 (until
     * then it was always one of ~7 fixed enum strings), so the field can now
     * carry an arbitrary exception message.
     */
    public const STATUS_MESSAGE_MAX = 60000;

    /**
     * Both models are $guarded = [], so whatever survives validation here is
     * mass-assigned. Every field used to be a bare ['nullable'] with no type
     * and no length.
     *
     * Deliberately NOT an in: whitelist on status_code, and deliberately not a
     * date rule on the timestamps: a rejected update returns 422, the runner
     * logs the failure and moves on (JobService::updateJobState swallows it),
     * and the job is then stuck at "running" for ever. Rules here may only
     * reject what could not possibly be a real runner payload.
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'queue_id' => ['nullable', 'integer'],
            'type' => ['nullable', 'string', 'max:255'],
            'status_code' => ['nullable', 'string', 'max:255'],
            'status_message' => ['nullable', 'string', 'max:'.self::STATUS_MESSAGE_MAX],
            'order' => ['nullable', 'integer'],
            'active' => ['nullable', 'boolean'],
            // started_at/finished_at are varchar(255) columns and the runner
            // writes an ISO-8601 string into them verbatim ("2026-08-27T07:54:03.008+0200").
            // Length is what the column needs protecting from; parseability is
            // not worth a 422.
            'started_at' => ['nullable', 'string', 'max:255'],
            'finished_at' => ['nullable', 'string', 'max:255'],
            'user_id' => ['nullable', 'integer'],
            'captcha_found' => ['nullable', 'boolean'],
            'captcha_solved' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Truncate an over-long diagnostic instead of rejecting the update.
     *
     * Same reasoning as above: losing the job's terminal state is far worse
     * than losing the tail of a stack trace. Cut on bytes (mb_strcut keeps
     * multibyte characters whole) so the result fits the TEXT column; the
     * max: rule counts characters, so a value trimmed to 60000 bytes always
     * passes it.
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
