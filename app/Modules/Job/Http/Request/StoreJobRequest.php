<?php

namespace App\Modules\Job\Http\Request;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreJobRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required'],
            'user_id' => ['required'],
            'type' => ['required','in:visitjob,waitjob,tryentertextfieldjob,entertextfieldjob,singleselecturljob,singleselectjob,tryswitchjob,switchjob,tryclickjob,clickjob,scrollbydurationjob,scrollbylengthjob,handlealertjob,tryhandlealertjob'],
            'description' => ['nullable'],
            'queue_id' => ['required'],
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
