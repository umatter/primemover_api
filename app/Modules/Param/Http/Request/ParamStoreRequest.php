<?php

namespace App\Modules\Param\Http\Request;

use App\Modules\Configuration\Model\Configuration;
use App\Modules\Param\ParamPayload;
use Closure;
use Illuminate\Support\Facades\Gate;

/**
 * POST params: as an update, plus the configuration the row belongs to.
 *
 * Someone else's configuration_id fails as "could not be found" rather than 403,
 * the same as App\Rules\OwnedParent: confirming that the id exists would leak
 * other researchers' rows.
 */
class ParamStoreRequest extends ParamUpdateRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'configuration_id' => ['required', 'integer', 'exists:configurations,id', $this->ownedConfiguration()],
            ...ParamPayload::rules(),
        ];
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return [
            ...ParamPayload::defaults(),
            ...parent::payload(),
            'configuration_id' => (int) $this->validated('configuration_id'),
        ];
    }

    private function ownedConfiguration(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $configuration = Configuration::find($value);

            if (null !== $configuration && Gate::denies('update', $configuration)) {
                $fail('The selected :attribute could not be found.');
            }
        };
    }
}
