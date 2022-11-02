<?php

namespace App\Modules\Proxy\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class ProxyIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function getPaged(): ?int
    {
        return $this->input('paged');
    }
}
