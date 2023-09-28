<?php

namespace App\Modules\Proxy\Http\Request;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

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
            'type' => 'required|string',
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
