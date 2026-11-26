<?php

namespace App\Modules\Configuration\Http\Request;

use App\Modules\Param\ParamPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * POST configurations: the configuration, its params and its preferences.
 *
 * params is optional; whatever it leaves out is filled from the control arm
 * (ParamPayload::defaults()), so every configuration created here can be run.
 */
class ConfigurationStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required'],
            'description' => ['nullable'],
            'active' => ['required'],
            'user_id' => ['required'],
            'params' => ['sometimes', 'array'],
            ...ParamPayload::rules('params.'),
            'preferences' => ['sometimes', 'array'],
            'preferences.*.name' => ['required', 'string', 'max:255', 'distinct'],
            // The column is NOT NULL, and the middleware has already turned "" and
            // whitespace into null: without `required` that was a 500, not a 422.
            'preferences.*.value' => ['required', 'string'],
            'preferences.*.description' => ['sometimes', 'nullable', 'string'],
            'preferences.*.active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * The configuration's own attributes.
     *
     * @return array<string, mixed>
     */
    public function configurationAttributes(): array
    {
        return $this->safe()->only(['name', 'description', 'active', 'user_id']);
    }

    /**
     * The params row to create: control-arm defaults overlaid with what was sent.
     *
     * @return array<string, mixed>
     */
    public function paramsAttributes(): array
    {
        $sent = (array) ($this->validated('params') ?? []);
        $sent = array_intersect_key($sent, array_flip([...ParamPayload::WEIGHTS, ...ParamPayload::LISTS]));

        return [
            ...ParamPayload::defaults(),
            ...ParamPayload::encodeLists($sent),
            'user_id' => Auth::id(),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function preferencesAttributes(): array
    {
        return array_map(fn (array $preference): array => [
            'name' => $preference['name'],
            'value' => $preference['value'],
            'description' => $preference['description'] ?? $preference['name'],
            'active' => $preference['active'] ?? true,
            'user_id' => Auth::id(),
        ], $this->validated('preferences') ?? []);
    }

    protected function prepareForValidation(): void
    {
        $params = $this->input('params');

        // An older client shape sent params as a one-element list.
        if (is_array($params) && array_is_list($params)) {
            $params = $params[0] ?? [];
        }

        $merge = ['user_id' => Auth::id()];

        if (is_array($params)) {
            $merge['params'] = ParamPayload::decodeLists($params);
        }

        $this->merge($merge);
    }
}
