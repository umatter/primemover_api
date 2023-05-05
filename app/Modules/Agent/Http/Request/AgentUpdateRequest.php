<?php

namespace App\Modules\Agent\Http\Request;

use App\Modules\Agent\Model\IdentificationEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'identification' => ['required', Rule::in(IdentificationEnum::values())],
            'multilogin_id' => ['string', 'nullable'],
            'multilogin_profile' => ['string', 'nullable'],
            'location' => ['string', 'nullable'],
            'active' => ['bool', 'nullable'],
            'user_id' => ['nullable'],
            'preferences' => ['nullable'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'identification' => $this->input('identification') ?? IdentificationEnum::default(),
        ]);
    }
}
