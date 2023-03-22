<?php

namespace App\Modules\Configuration\Http\Service;

use App\Modules\Configuration\Facade\Factory\ConfigurationFactory;
use App\Modules\Configuration\Model\Configuration;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ConfigurationService
{
    protected Configuration $configuration;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }
    
    public function getData(): QueryBuilder
    {
        $configurations = QueryBuilder::for($this->configuration)
            ->allowedFilters([
                AllowedFilter::scope('search'),
                AllowedFilter::scope('after'),
                AllowedFilter::scope('recent'),
            ])
            ->with(['params', 'preferences'])
            ->latest();

        return $configurations;
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