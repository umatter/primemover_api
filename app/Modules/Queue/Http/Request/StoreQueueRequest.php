<?php

namespace App\Modules\Queue\Http\Request;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreQueueRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required'],
            'description' => ['required'],
            'user_id' => ['required'],
            'crawler_id' => ['required'],
            'active' => [],
            'new' => [],
            'processed' => [],
            'processing' => [],
            'reviewed' => [],
            'status_code' => [],
            'status_message' => [],
            'order' => [],
            'start_at' => [],
            'updated_at' => [],
            'created_at' => [],
            'started_at' => [],
            'finished_at' => [],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'user_id' => Auth::id() ?? 1,
        ]);
    }
}
