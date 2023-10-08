<?php

namespace App\Modules\Queue\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQueueRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => [],
            'description' => [],
            'active' => [],
            'new' => [],
            'processed' => [],
            'timezone' => [],
            'timezone_utc_offset' => [],
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
}
