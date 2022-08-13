<?php

namespace App\Http\Requests;

use App\Facades\Services\DataMapper;
use App\Models\DTO\Preference;
use Exception;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

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
        ];
    }

    /**
     * @throws Exception
     */
    public function getPreferences(): ?Collection
    {
        $preferences = $this->input('preferences');
        if (!$preferences) {
            return null;
        }

        return DataMapper::disableEnforceType()
            ->removeUndefinedAttributes()
            ->mapList($preferences, Preference::class);
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'user_id' => Auth::id(),
        ]);
    }
}
