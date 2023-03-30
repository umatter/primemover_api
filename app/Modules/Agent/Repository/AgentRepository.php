<?php

namespace App\Modules\Agent\Repository;

use App\Modules\Agent\Model\Agent;
use Illuminate\Database\Eloquent\Model;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Tests\Feature\Modules\Agent\Service\AgentServiceTest;

/**
 * @see AgentServiceTest
 */
class AgentRepository
{
    public function __construct(protected Agent $agent)
    {
    }

    public function getData(): QueryBuilder
    {
        return QueryBuilder::for($this->agent)
            ->allowedFilters([
                AllowedFilter::scope('search'),
                AllowedFilter::scope('after'),
                AllowedFilter::scope('recent'),
            ])
            ->with('preferences')
            ->latest();
    }

    public function additional($agents): array
    {
        return [
            'values' => [
                'name' => $agents->pluck('name')->unique(),
                'identification' => $agents->pluck('identification')->unique(),
                'multilogin_id' => $agents->pluck('multilogin_id')->unique(),
                'multilogin_profile' => $agents->pluck('multilogin_profile')->unique(),
                'location' => $agents->pluck('location')->unique(),
            ],
            'count' => $agents->count(),
        ];
    }

    public function getById($id): Agent
    {
        return $this->agent->query()
            ->with(['preferences'])
            ->findOr($id, fn () => ['status' => false]);
    }

    public function save(array $data): Agent|Model
    {
        return $this->agent->query()->create($data);
    }

    public function update(array $data, Agent $agent): Agent
    {
        $this->agent = $agent;
        $this->agent->update($data);

        return $this->agent;
    }

    public function delete(Agent $agent): array
    {
        $this->agent = $agent;

        return ['status' => $agent->delete()];
    }
}
