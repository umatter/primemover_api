<?php

namespace App\Modules\Crawler\Http\Request;

use App\Modules\Experiment\Model\Experiment;
use App\Rules\OwnedParent;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCrawlerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'experiment_id' => ['required', new OwnedParent(Experiment::class)],
            // The setup. Absent: unchanged. Without these rules validated() dropped
            // them, so a PUT answered 200 and changed nothing (umatter/primemover_api#90).
            'agent_id' => ['sometimes', 'nullable', 'exists:agents,id'],
            'proxy_id' => ['sometimes', 'nullable', 'exists:proxies,id'],
            'configuration_id' => ['sometimes', 'nullable', 'exists:configurations,id'],
            'description' => ['sometimes', 'nullable', 'string'],
            'testing' => ['sometimes', 'boolean'],
        ];
    }
}
