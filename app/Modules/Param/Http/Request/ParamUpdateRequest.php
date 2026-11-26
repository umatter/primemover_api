<?php

namespace App\Modules\Param\Http\Request;

use App\Modules\Param\ParamPayload;
use Illuminate\Foundation\Http\FormRequest;

/**
 * PUT params/{id}: the weights and the two lists, nothing else.
 *
 * configuration_id and user_id are deliberately absent from the rules, so
 * payload() can never carry them: a param cannot be moved to another
 * configuration or re-stamped through this route.
 */
class ParamUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('param'));
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return ParamPayload::rules();
    }

    /**
     * The validated attributes, lists in their canonical wire form.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return ParamPayload::encodeLists($this->safe()->only([...ParamPayload::WEIGHTS, ...ParamPayload::LISTS]));
    }

    protected function prepareForValidation(): void
    {
        $this->replace(ParamPayload::decodeLists($this->all()));
    }
}
