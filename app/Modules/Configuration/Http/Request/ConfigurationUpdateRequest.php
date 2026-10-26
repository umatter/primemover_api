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
            // The column is NOT NULL, and the middleware has already turned "" and
            // whitespace into null: without `required` that was a 500, not a 422.
            'preferences.*.value' => ['required', 'string'],
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

        // Values are strings, but pmspec's `updates:` blocks come from YAML, so a
        // weight such as 0.3 arrives as a number. Keep storing its string form.
        $preferences = $this->input('preferences');
        if (is_array($preferences)) {
            $this->merge(['preferences' => array_map(function ($preference) {
                if (is_array($preference) && (is_int($preference['value'] ?? null) || is_float($preference['value'] ?? null))) {
                    $preference['value'] = (string) $preference['value'];
                }

                return $preference;
            }, $preferences)]);
        }
    }
}
