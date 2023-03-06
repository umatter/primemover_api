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
            'queue_id' => ['required'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'user_id' => Auth::id() ?? 1,
        ]);
    }
}
