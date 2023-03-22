<?php

namespace App\Modules\Agent\Http\Service;

use App\Modules\Agent\Model\Agent;
use Illuminate\Database\Eloquent\Collection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AgentService
{
    protected Agent $agent;

    public function __construct(Agent $agent)
    {
        $this->agent = $agent;
    }
    
    public function getData()
    {
        $agents = QueryBuilder::for($this->agent)
            ->allowedFilters([
                AllowedFilter::scope('search'),
                AllowedFilter::scope('after'),
                AllowedFilter::scope('recent'),
            ])
            ->with('preferences')
            ->latest();

        return $agents;
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

    public function save(array $data): Agent
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