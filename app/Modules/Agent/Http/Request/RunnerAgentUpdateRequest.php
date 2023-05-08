<?php

namespace App\Modules\Agent\Http\Request;

use App\Modules\Agent\Model\IdentificationEnum;
use Illuminate\Validation\Rule;

class RunnerAgentUpdateRequest extends AgentUpdateRequest
{
    public function rules(): array
    {
        return [
            'name' => ['string'],
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
}
