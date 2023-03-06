<?php

namespace App\Modules\Job\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJobRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required'],
            'queue_id' => ['required'],
        ];
    }
}
