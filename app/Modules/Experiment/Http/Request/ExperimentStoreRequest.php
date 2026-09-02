<?php

namespace App\Modules\Experiment\Http\Request;

use App\Access\Isolation;
use App\Facades\Services\DataMapper;
use App\Models\DTO\Preference;
use Closure;
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
            // PHP's 'integer' rule accepts JSON booleans (true casts to 1), so
            // reject them explicitly before that check runs.
            'runner_user_id' => [
                'nullable',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (is_bool($value)) {
                        $fail("The {$attribute} must be an integer runner account id.");
                    }
                },
                'integer',
                Rule::in(Isolation::serviceAccountIds()),
            ],
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
