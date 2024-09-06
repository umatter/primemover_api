<?php

namespace App\Modules\Job\Http\Request;

use App\Modules\Job\Enum\JobType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJobRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => [],
            'queue_id' => [],
            'previous_job_id' => ['required', 'exists:App\Modules\Job\Model\Job,id'],
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
