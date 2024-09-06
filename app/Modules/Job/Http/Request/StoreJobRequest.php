<?php

namespace App\Modules\Job\Http\Request;

use App\Modules\Job\Enum\JobType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreJobRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required'],
            'user_id' => ['required'],
            'type' => ['required', Rule::in(JobType::values())],
            'description' => ['nullable'],
            'queue_id' => ['required'],
            'previous_job_id' => ['required', 'exists:App\Modules\Job\Model\Job,id'],
            'status_code' => [],
            'status_message' => [],
            'order' => [],
            'active' => [],
            'started_at' => [],
            'finished_at' => [],
            'captcha_found' => [],
            'captcha_solved' => [],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'user_id' => Auth::id(),
        ]);
    }
}
