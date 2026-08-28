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
        ];
    }
}
