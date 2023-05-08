<?php

namespace App\Modules\Queue\Http\Request;

class RunnerQueueUpdateRequest extends UpdateQueueRequest
{
    public function rules(): array
    {
        return [
            'name' => ['nullable'],
            'description' => ['nullable'],
            'active' => ['nullable'],
            'new' => ['nullable'],
            'processed' => ['nullable'],
            'processing' => ['nullable'],
            'reviewed' => ['nullable'],
            'status_code' => ['nullable'],
            'status_message' => ['nullable'],
            'order' => ['nullable'],
            'start_at' => ['nullable'],
            'updated_at' => ['nullable'],
            'created_at' => ['nullable'],
            'started_at' => ['nullable'],
            'finished_at' => ['nullable'],
        ];
    }
}
