<?php

namespace App\Modules\Experiment\Http\Request;

use App\Access\Isolation;
use App\Facades\Services\DataMapper;
use App\Models\DTO\Preference;
use Exception;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ExperimentStoreRequest extends FormRequest
{
    public const EMPTY_FIELD = 'Value not provided';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required'],
            'description' => ['nullable'],
            'user_id' => ['required'],
            'contact' => ['nullable'],
            // Which runner executes this experiment. Only a configured runner
            // account is a valid target; null/absent = the default runner.
            'runner_user_id' => ['nullable', 'integer', Rule::in(Isolation::serviceAccountIds())],
        ];
    }

    /**
     * @throws Exception
     */
    public function getPreferences(): ?Collection
    {
        $preferences = $this->input('preferences');
        if ( ! $preferences) {
            return null;
        }

        return DataMapper::disableEnforceType()
            ->removeUndefinedAttributes()
            ->mapList($preferences, Preference::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'user_id' => Auth::id(),
        ]);
    }
}
