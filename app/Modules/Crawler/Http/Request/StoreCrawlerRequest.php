<?php

namespace App\Modules\Crawler\Http\Request;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreCrawlerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'experiment_id' => ['required'],
            'user_id' => ['required'],
            // A bot is composed from Library building blocks. These are nullable columns on the
            // crawlers table; without them here, $request->validated() silently strips them and
            // the crawler is created with no agent/proxy/configuration.
            'agent_id' => ['nullable'],
            'proxy_id' => ['nullable'],
            'configuration_id' => ['nullable'],
            'description' => ['nullable'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'user_id' => Auth::id() ?? 1,
        ]);
    }
}
