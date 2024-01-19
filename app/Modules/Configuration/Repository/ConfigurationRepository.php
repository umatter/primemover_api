<?php

namespace App\Modules\Configuration\Repository;

use App\Modules\Configuration\Facade\Factory\ConfigurationFactory;
use App\Modules\Configuration\Model\Configuration;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ConfigurationRepository
{
    public function __construct(protected Configuration $configuration)
    {
    }

    public function all(): QueryBuilder
    {
        return QueryBuilder::for($this->configuration)
            ->allowedSorts(['id', 'name', 'created_at', 'updated_at'])
            ->defaultSort('-id')
            ->allowedFilters([
                AllowedFilter::scope('search'),
                AllowedFilter::scope('after'),
                AllowedFilter::scope('recent'),
                AllowedFilter::scope('crawler'),
            ])
            ->with(['params', 'preferences']);
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

    public function save(array $data): Configuration
    {
        $configuration = ConfigurationFactory::create($data);
        $configuration->save();

        return $configuration;
    }

    public function update(array $data, Configuration $configuration): array
    {
        return [
            'status' => $configuration->update($data),
            'data' => $configuration->fresh(),
        ];
    }

    public function delete(Configuration $configuration): array
    {
        return ['status' => $configuration->delete()];
    }
}
