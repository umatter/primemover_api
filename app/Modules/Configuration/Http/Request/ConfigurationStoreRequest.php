<?php

namespace App\Modules\Configuration\Http\Request;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ConfigurationStoreRequest extends FormRequest
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
            'active' => ['required'],
            'user_id' => ['required'],
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
        $this->merge([
            'user_id' => Auth::id(),
        ])->except(['params', 'preferences']);
    }
}
