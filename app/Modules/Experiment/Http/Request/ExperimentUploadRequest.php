<?php

namespace App\Modules\Experiment\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class ExperimentUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'json' => ['required'],
            'json.*.name' => ['required', 'string', 'max:255'],
            'json.*.description' => ['nullable', 'string', 'max:255'],
            'json.*.configuration.name' => ['required', 'string', 'max:255'],
            'json.*.configuration.description' => ['required', 'string', 'max:255'],
            'json.*.configuration.active' => ['required', 'integer'],
            'json.*.configuration.params' => ['nullable'],
            'json.*.configuration.params.search_terms' => ['nullable', 'array'],
            'json.*.configuration.params.media_outlet_urls' => ['nullable', 'array'],
            'json.*.configuration.preferences' => ['nullable', 'array'],
            'json.*.proxy' => ['nullable'],
            'json.*.proxy.name' => ['required', 'string', 'max:255'],
            'json.*.proxy.description' => ['nullable', 'string', 'max:255'],
            'json.*.proxy.hostname' => ['nullable', 'string', 'max:255'],
            'json.*.proxy.port' => ['nullable', 'integer', 'max:65535'],
            'json.*.proxy.username' => ['required_unless:type,http', 'string', 'max:255'],
            'json.*.proxy.password' => ['required_unless:type,http', 'string', 'max:255'],
            'json.*.proxy.active' => ['required', 'boolean'],
            'json.*.proxy.type' => ['required', 'string'],
            'json.*.agent' => ['required'],
            'json.*.agent.name' => ['required', 'string', 'max:255'],
            'json.*.agent.description' => ['required', 'string', 'max:255'],
            'json.*.agent.identification' => ['required', 'string', 'max:255'],
            'json.*.agent.multilogin_id' => ['nullable', 'string', 'max:255'],
            'json.*.agent.multilogin_profile' => ['required'],
            'json.*.agent.location' => ['required'],
            'json.*.agent.active' => ['required', 'boolean'],
            'json.*.queues' => ['required', 'array'],
            'json.*.queues.*.timezone' => ['required', 'string', 'max:255'],
            'json.*.queues.*.timezone_utc_offset' => ['required', 'string', 'max:6'],
            'json.*.queues.*.name' => ['required', 'string', 'max:255'],
            'json.*.queues.*.description' => ['required', 'string', 'max:255'],
            'json.*.queues.*.start_at' => ['required', 'string', 'max:255'],
            'json.*.queues.*.active' => ['required', 'boolean'],
            'json.*.queues.*.jobs' => ['required', 'array'],
            'json.*.queues.*.jobs.*.name' => ['required', 'string', 'max:255'],
            'json.*.queues.*.jobs.*.type' => ['required', 'string', 'max:255'],
            'json.*.queues.*.jobs.*.description' => ['required', 'string', 'max:255'],
            'json.*.queues.*.jobs.*.active' => ['required', 'boolean'],
            'json.*.queues.*.jobs.*.behaviors' => ['required', 'array'],
            'json.*.queues.*.jobs.*.behaviors.*.name' => ['required', 'string', 'max:255'],
            'json.*.queues.*.jobs.*.behaviors.*.description' => ['nullable', 'string', 'max:255'],
            'json.*.queues.*.jobs.*.behaviors.*.active' => ['required', 'boolean'],
            'json.*.queues.*.jobs.*.behaviors.*.value' => ['required'],
        ];
    }

    public function getJsonContent(): array
    {
        return $this->input('json');
    }
}
