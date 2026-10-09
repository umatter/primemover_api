<?php

namespace App\Modules\Configuration\Repository;

use App\Modules\Configuration\Facade\Factory\ConfigurationFactory;
use App\Modules\Configuration\Model\Configuration;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;

class ConfigurationRepository
{
    public function __construct(protected Configuration $configuration) {}

    public function all(): QueryBuilder
    {
        return Configuration::queryBuilder()->with(['params', 'preferences']);
    }

    public function additional($configurations): array
    {
        return [
            'values' => [
                'name' => $configurations->pluck('name')->unique(),
            ],
            'count' => $configurations->count(),
        ];
    }

    public function getById(Configuration $configuration): Configuration
    {
        return $configuration->load(['crawler', 'params', 'preferences']);
    }

    /**
     * Creates the configuration and, when given, its params row and preferences
     * -- all or nothing, so a failed child never leaves a configuration without
     * a treatment behind.
     *
     * @param array<string, mixed>|null        $params
     * @param array<int, array<string, mixed>> $preferences
     */
    public function save(array $data, ?array $params = null, array $preferences = []): Configuration
    {
        return DB::transaction(function () use ($data, $params, $preferences): Configuration {
            $configuration = ConfigurationFactory::create($data);
            $configuration->save();

            if (null !== $params) {
                $configuration->params()->create($params);
            }

            foreach ($preferences as $preference) {
                $configuration->preferences()->create($preference);
            }

            return $configuration->load(['params', 'preferences']);
        });
    }

    public function update(array $data, Configuration $configuration): array
    {
        $status = $configuration->update(Arr::except($data, ['preferences']));

        if (array_key_exists('preferences', $data)) {
            foreach ($data['preferences'] as $preference) {
                $existing = $configuration->preferences()->where('name', $preference['name'])->first();

                $configuration->preferences()->updateOrCreate(
                    ['name' => $preference['name']],
                    [
                        'value' => $preference['value'],
                        'description' => $preference['description'] ?? $existing?->description,
                        'active' => 1,
                        'user_id' => Auth::id(),
                    ]
                );
            }
        }

        return [
            'status' => $status,
            'data' => $configuration->fresh()->load('preferences'),
        ];
    }

    public function delete(Configuration $configuration): array
    {
        return ['status' => $configuration->delete()];
    }
}
