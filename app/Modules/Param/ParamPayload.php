<?php

namespace App\Modules\Param;

use Closure;

/**
 * The shape of a params payload, shared by params/{id} and configurations.
 *
 * search_terms and media_outlet_urls travel as JSON-encoded STRINGS, in both
 * directions. The runner's SearchTermDeserializer / MediaOutletsDeserializer
 * call getText() on the field and parse it, so a real array on the wire
 * deserialises to an empty list -- the bot silently loses its treatment.
 *
 * Clients may therefore send either an array or a JSON string. Both are decoded
 * so the items can be validated, then re-encoded to one canonical string before
 * they reach the model. Same input, same stored bytes, whichever form it came in.
 */
class ParamPayload
{
    public const WEIGHTS = ['pi', 'psi', 'kappa', 'tau', 'alpha', 'beta'];

    public const LISTS = ['search_terms', 'media_outlet_urls'];

    /**
     * Validation rules, optionally nested under a prefix such as "params.".
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(string $prefix = ''): array
    {
        $position = ['numeric', 'between:-1,1'];
        $list = self::listRule();

        return [
            $prefix.'pi' => ['sometimes', 'numeric', 'between:-1,1'],
            $prefix.'psi' => ['sometimes', 'numeric'],
            $prefix.'kappa' => ['sometimes', 'integer', 'min:0'],
            $prefix.'tau' => ['sometimes', 'numeric'],
            $prefix.'alpha' => ['sometimes', 'numeric'],
            $prefix.'beta' => ['sometimes', 'numeric'],

            $prefix.'search_terms' => ['sometimes', 'nullable', $list],
            $prefix.'search_terms.*' => ['array'],
            $prefix.'search_terms.*.term' => ['required', 'string'],
            $prefix.'search_terms.*.pi' => ['required', ...$position],

            $prefix.'media_outlet_urls' => ['sometimes', 'nullable', $list],
            $prefix.'media_outlet_urls.*' => ['array'],
            $prefix.'media_outlet_urls.*.domain' => ['required', 'string'],
            $prefix.'media_outlet_urls.*.url' => ['nullable', 'string'],
            $prefix.'media_outlet_urls.*.pi' => ['required', ...$position],
        ];
    }

    /**
     * Turns JSON-string lists into arrays so the item rules can see them.
     *
     * A string that is not a JSON *list* is left as it is, so the 'array' rule
     * rejects it rather than it being silently dropped.
     *
     * @param  array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function decodeLists(array $data): array
    {
        foreach (self::LISTS as $list) {
            if ( ! isset($data[$list]) || ! is_string($data[$list])) {
                continue;
            }

            $decoded = json_decode($data[$list], true);

            if (is_array($decoded) && array_is_list($decoded)) {
                $data[$list] = $decoded;
            }
        }

        return $data;
    }

    /**
     * Back to the canonical wire form, keeping only the validated keys.
     *
     * @param  array<string, mixed> $validated
     * @return array<string, mixed>
     */
    public static function encodeLists(array $validated): array
    {
        foreach (self::LISTS as $list) {
            if (array_key_exists($list, $validated)) {
                $validated[$list] = json_encode(array_values($validated[$list] ?? []));
            }
        }

        return $validated;
    }

    /**
     * A new configuration's params: the control arm, no lists.
     *
     * Control is pi = psi = kappa = tau = 0 -- the rule the runner and the
     * decision function use to tell control from treatment.
     *
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return array_fill_keys(self::WEIGHTS, 0)
            + array_fill_keys(self::LISTS, json_encode([]));
    }

    /**
     * A JSON list (0, 1, 2 ...), not a JSON object, which decodes to an
     * associative array and would pass Laravel's plain 'array' rule. Laravel 10
     * has no built-in 'list' rule.
     */
    private static function listRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ( ! is_array($value) || ! array_is_list($value)) {
                $fail('The :attribute must be a list, as an array or a JSON string holding one.');
            }
        };
    }
}
