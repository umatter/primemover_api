<?php

namespace App\Modules\Behavior\Repository;

use App\Modules\Behavior\Model\Behavior;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class BehaviorRepository
{
    /** Page size when the caller does not ask; unchanged for existing clients. */
    public const DEFAULT_PER_PAGE = 10;

    /** Enough for a whole run's behaviours in one page. */
    public const MAX_PER_PAGE = 500;

    public function __construct(protected Behavior $behavior) {}

    public function all(): LengthAwarePaginator
    {
        $perPage = (int) request()->query('per_page', self::DEFAULT_PER_PAGE);

        return Behavior::queryBuilder()
            ->with(['job', 'user'])
            ->paginate(max(1, min(self::MAX_PER_PAGE, $perPage)))
            ->withQueryString();
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
