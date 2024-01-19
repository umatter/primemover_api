<?php

namespace App\Modules\Agent\Repository;

use App\Modules\Agent\Model\Agent;
use Illuminate\Database\Eloquent\Model;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AgentRepository
{
    public function __construct(protected Agent $agent)
    {
    }

    public function all(): QueryBuilder
    {
        return QueryBuilder::for($this->agent)
            ->allowedSorts(['id', 'name', 'created_at', 'updated_at'])
            ->defaultSort('-id')
            ->allowedFilters([
                AllowedFilter::scope('search'),
                AllowedFilter::scope('after'),
                AllowedFilter::scope('recent'),
                AllowedFilter::scope('crawler'),
            ])
            ->with('preferences');
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

    public function getById(int $id): Agent|Model
    {
        return $this->agent->query()
            ->with(['preferences', 'crawler'])
            ->findOrFail($id);
    }

    public function save(array $data): Agent|Model
    {
        return $this->agent->query()->create($data);
    }

    public function update(array $data, Agent $agent): Agent
    {
        $this->agent = $agent;

        $this->agent->update($data);

        return $this->agent->fresh();
    }

    public function delete(Agent $agent): array
    {
        $this->agent = $agent;

        return ['status' => $agent->delete()];
    }
}
