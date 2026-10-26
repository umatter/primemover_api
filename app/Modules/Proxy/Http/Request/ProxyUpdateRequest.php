<?php

namespace App\Modules\Proxy\Http\Request;

use App\Modules\Proxy\Model\Proxy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * PUT proxies/{id}. The controller used to write $request->all() into a
 * $guarded = [] model, so any column -- user_id included -- was writable and
 * nothing was validated. Absent fields stay as they are.
 */
class ProxyUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string'],
            'description' => ['sometimes', 'nullable', 'string'],
            'hostname' => ['sometimes', 'required', 'string'],
            'port' => ['sometimes', 'required', 'integer'],
            'type' => ['sometimes', 'required', 'string', Rule::in(Proxy::TYPES)],
            'username' => ['sometimes', 'nullable', 'string', 'required_unless:type,http'],
            'password' => ['sometimes', 'nullable', 'string', 'required_unless:type,http'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
