<?php

namespace App\Modules\Agent\Http\Request;

use App\Modules\Agent\Model\IdentificationEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

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
            'identification' => ['required', Rule::in(IdentificationEnum::values())],
            'multilogin_id' => ['nullable'],
            'multilogin_profile' => ['nullable'],
            'location' => ['nullable'],
            'user_id' => ['required'],
            'active' => [],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'user_id' => Auth::id(),
            'active' => true,
            'identification' => $this->input('identification', IdentificationEnum::default()),
            'multilogin_profile' => $this->input('multiLoginProfile'),
            'multilogin_id' => $this->input('multiLoginId'),
        ]);
    }
}
