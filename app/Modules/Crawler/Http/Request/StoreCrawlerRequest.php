<?php

namespace App\Modules\Crawler\Http\Request;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreCrawlerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'experiment_id' => ['required'],
            'user_id' => ['required'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'user_id' => Auth::id() ?? 1,
        ]);
    }
}
