<?php

namespace App\Modules\Report\Http\Request;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ReportStoreRequest extends FormRequest
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
            'filename' => ['required'],
            'active' => ['required'],
            'description' => ['nullable'],
            'user_id' => ['required'],
            'filecontent' => ['nullable'],
            'path' => ['required'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'user_id' => Auth::id(),
            'active' => 1,
        ]);
    }
}
