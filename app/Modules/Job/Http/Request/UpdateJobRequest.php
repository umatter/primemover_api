<?php

namespace App\Modules\Job\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJobRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required'],
            'description' => [],
            'queue_id' => [],
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
