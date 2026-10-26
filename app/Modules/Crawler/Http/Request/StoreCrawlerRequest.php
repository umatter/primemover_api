<?php

namespace App\Modules\Crawler\Http\Request;

use App\Modules\Agent\Model\Agent;
use App\Modules\Configuration\Model\Configuration;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Proxy\Model\Proxy;
use App\Rules\AttachableToCrawler;
use App\Rules\OwnedParent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreCrawlerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'experiment_id' => ['required', new OwnedParent(Experiment::class)],
            'user_id' => ['required'],
            // A bot is composed from Library building blocks. These are nullable columns on the
            // crawlers table; without them here, $request->validated() silently strips them and
            // the crawler is created with no agent/proxy/configuration. Another study's
            // item is refused: what a bot does with it is shared (AttachableToCrawler).
            'agent_id' => ['nullable', 'integer', 'exists:agents,id', new AttachableToCrawler(Agent::class, 'agent_id')],
            'proxy_id' => ['nullable', 'integer', 'exists:proxies,id', new AttachableToCrawler(Proxy::class, 'proxy_id')],
            'configuration_id' => ['nullable', 'integer', 'exists:configurations,id', new AttachableToCrawler(Configuration::class, 'configuration_id')],
            'description' => ['nullable'],
            'testing' => ['sometimes', 'boolean'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'user_id' => Auth::id() ?? 1,
        ]);
    }
}
