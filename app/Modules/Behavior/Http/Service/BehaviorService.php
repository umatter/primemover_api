<?php

namespace App\Modules\Behavior\Http\Service;

use App\Modules\Behavior\Model\Behavior;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class BehaviorService
{
    protected Behavior $behavior;

    public function __construct(Behavior $behavior)
    {
        $this->behavior = $behavior;
    }
    
    public function getData(): LengthAwarePaginator
    {
        $behavior = $this->behavior->with(['job', 'user'])->latest()->paginate(10);

        return $behavior;
    }
    
    public function getById($id): Behavior
    {
        $behavior = $this->behavior->with(['user', 'job'])->findOrFail($id);

        return $behavior;
    }

    public function save(array $data): Behavior
    {
        $data['user_id'] = Auth::id() ?? 1;

        return $this->behavior->query()->create($data);
    }

    public function update(array $data, Behavior $behavior): Behavior
    {
        $this->behavior = $behavior;
        $this->behavior->update($data);

        return $this->behavior;
    }

    public function delete(Behavior $behavior): void
    {
        $behavior->delete();
    }
    
}