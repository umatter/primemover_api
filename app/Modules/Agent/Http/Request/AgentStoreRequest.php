<?php

namespace App\Modules\Agent\Http\Request;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class AgentStoreRequest extends FormRequest
{
    public const EMPTY_FIELD = 'Value not provided';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required'],
            'description' => ['nullable'],
            'identification' => ['nullable'],
            'multilogin_id' => ['nullable'],
            'multilogin_profile' => ['nullable'],
            'location' => ['nullable'],
            'user_id' => ['required'],
            'active' => ['required'],
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'user_id' => Auth::id(),
        ]);
    }
}
