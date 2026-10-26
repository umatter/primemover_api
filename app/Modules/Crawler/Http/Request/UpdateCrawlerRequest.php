<?php

namespace App\Modules\Crawler\Http\Request;

use App\Modules\Agent\Model\Agent;
use App\Modules\Configuration\Model\Configuration;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Proxy\Model\Proxy;
use App\Rules\AttachableToCrawler;
use App\Rules\OwnedParent;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCrawlerRequest extends FormRequest
{
    public function rules(): array
    {
        $crawler = $this->route('crawler');
        $crawler = $crawler instanceof Crawler ? $crawler : null;

        return [
            'name' => ['required', 'string'],
            'experiment_id' => ['required', new OwnedParent(Experiment::class)],
            // The setup. Absent: unchanged. Without these rules validated() dropped
            // them, so a PUT answered 200 and changed nothing (umatter/primemover_api#90).
            // Attaching another study's item is refused; keeping the current one is not.
            'agent_id' => ['sometimes', 'nullable', 'exists:agents,id', new AttachableToCrawler(Agent::class, 'agent_id', $crawler)],
            'proxy_id' => ['sometimes', 'nullable', 'exists:proxies,id', new AttachableToCrawler(Proxy::class, 'proxy_id', $crawler)],
            'configuration_id' => ['sometimes', 'nullable', 'exists:configurations,id', new AttachableToCrawler(Configuration::class, 'configuration_id', $crawler)],
            'description' => ['sometimes', 'nullable', 'string'],
            'testing' => ['sometimes', 'boolean'],
        ];
    }
}
