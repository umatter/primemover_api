<?php

namespace App\Modules\Configuration\Http\Request;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ConfigurationUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required'],
            'description' => ['nullable'],
            // Optional: absent keeps the current value, so a client editing
            // preferences never has to guess it.
            'active' => ['sometimes'],
            'preferences' => ['sometimes', 'array'],
            'preferences.*.name' => ['required', 'string', 'max:255'],
            'preferences.*.value' => ['present'],
            'preferences.*.description' => ['sometimes', 'nullable', 'string'],
            'preferences.*.active' => ['sometimes', 'boolean'],
        ];
    }

    public function getParams()
    {
        return $this->input('params', []);
    }

    public function getParamsList(): array
    {
        $paramsData = Collection::make($this->getParams())->first() ?? [];

        return Arr::add($paramsData, 'user_id', Auth::id());
    }

    protected function prepareForValidation(): void
    {
        $this->except(['params']);
    }
}
