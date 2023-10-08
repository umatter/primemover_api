<?php

namespace App\Modules\Report\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class ReportUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'job_id' => ['required'],
            'mimetype' => ['required'],
        ];
    }
}
