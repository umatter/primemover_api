<?php

namespace App\Modules\Job\Http\Request;

class RunnerJobUpdateRequest extends UpdateJobRequest
{
    public function rules(): array
    {
        return [
            'name' => ['nullable'],
            'description' => ['nullable'],
            'queue_id' => ['nullable'],
            'type' => ['nullable'],
            'status_code' => ['nullable'],
            'status_message' => ['nullable'],
            'order' => ['nullable'],
            'active' => ['nullable'],
            'started_at' => ['nullable'],
            'finished_at' => ['nullable'],
            'user_id' => ['nullable'],
            'captcha_found' => ['nullable'],
            'captcha_solved' => ['nullable'],
        ];
    }
}
