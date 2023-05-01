<?php

namespace App\Modules\Agent\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class AgentUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['string', 'required'],
            'description' => ['string', 'nullable'],
            'identification' => ['string', 'nullable'],
            'multilogin_id' => ['string', 'nullable'],
            'multilogin_profile' => ['string', 'nullable'],
            'location' => ['string', 'nullable'],
            'active' => ['bool', 'nullable'],
            'user_id' => ['nullable'],
            'preferences' => ['nullable'],
        ];
    }
}
