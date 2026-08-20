<?php

namespace App\Modules\Crawler\Http\Request;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\OwnedParent;
use App\Modules\Experiment\Model\Experiment;

class UpdateCrawlerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'experiment_id' => ['required', new OwnedParent(Experiment::class)],
        ];
    }
}
