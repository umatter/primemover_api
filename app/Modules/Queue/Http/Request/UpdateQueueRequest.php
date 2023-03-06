<?php

namespace App\Modules\Queue\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQueueRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required'],
            'description' => ['required'],
            'crawler_id' => ['required'],
        ];
    }
}
