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
            'name' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'identification' => ['required', Rule::in(IdentificationEnum::values())],
            'multilogin_id' => ['nullable', 'string'],
            'multilogin_profile' => ['nullable', 'string'],
            'location' => ['nullable', 'string'],
            'active' => ['nullable', 'bool'],
            'user_id' => ['nullable', 'integer'],
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
