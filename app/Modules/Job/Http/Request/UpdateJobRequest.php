<?php

namespace App\Modules\Job\Http\Request;

use App\Modules\Job\Enum\JobType;
use App\Modules\Queue\Model\Queue;
use App\Rules\OwnedParent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJobRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => [],
            'queue_id' => [new OwnedParent(Queue::class)],
            // Nullable: the first job in a queue has no predecessor, and reordering must be able
            // to update it. The runner sequences by `order` and rebuilds the chain at runtime, so a
            // null previous_job_id is fine. Mirrors StoreJobRequest.
            'previous_job_id' => ['nullable', 'exists:App\Modules\Job\Model\Job,id'],
            'description' => [],
            'type' => [Rule::in(JobType::values())],
            'status_code' => [],
            'status_message' => [],
            'order' => [],
            'active' => [],
            'started_at' => [],
            'finished_at' => [],
            'user_id' => [],
            'captcha_found' => [],
            'captcha_solved' => [],
        ];
    }
}
