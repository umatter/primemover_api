<?php

namespace App\Modules\Behavior\Repository;

use App\Modules\Behavior\Model\Behavior;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class BehaviorRepository
{
    public function __construct(protected Behavior $behavior) {}

    public function all(): LengthAwarePaginator
    {
        return Behavior::queryBuilder()
            ->with(['job', 'user'])
            ->paginate(10);
    }

    public function getById(int $id): Behavior|Model
    {
        return $this->behavior->with(['user', 'job'])->findOrFail($id);
    }

    public function save(array $data): Behavior|Model
    {
        $data['user_id'] = Auth::id() ?? 1;

        return $this->behavior->query()->create($data);
    }

    public function update(array $data, Behavior $behavior): Behavior
    {
        $this->behavior = $behavior;
        $this->behavior->update($data);

        return $this->behavior->fresh();
    }

    public function delete(Behavior $behavior): void
    {
        $behavior->delete();
    }
}
