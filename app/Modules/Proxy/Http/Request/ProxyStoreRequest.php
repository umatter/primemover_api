<?php

namespace App\Modules\Proxy\Http\Request;

use App\Modules\Proxy\Model\Proxy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProxyStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'description' => 'nullable|string',
            'hostname' => 'required|string',
            'port' => 'required|integer',
            'type' => ['required', 'string', Rule::in(Proxy::TYPES)],
            'username' => 'required_unless:type,http',
            'password' => 'required_unless:type,http',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'user_id' => Auth::id(),
            'active' => true,
        ]);
    }
}
