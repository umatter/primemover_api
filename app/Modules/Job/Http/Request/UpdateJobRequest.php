<?php

namespace App\Modules\Job\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJobRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => [],
            'queue_id' => [],
            'previous_job_id' => ['required', 'exists:App\Modules\Job\Model\Job,id'],
            'description' => [],
            'type' => [],
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
