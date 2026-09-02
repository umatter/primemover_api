<?php

namespace App\Modules\Agent\Repository;

use App\Modules\Agent\Model\Agent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class AgentRepository
{
    public function __construct(protected Agent $agent) {}

    public function all(?int $perPage = null): LengthAwarePaginator
    {
        return Agent::queryBuilder()
            ->with('preferences')
            ->fastPaginate($perPage ?? request()->input('per_page', 15));
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
