<?php

namespace App\Modules\Outlet\Repository;

use App\Modules\Outlet\Model\Outlet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class OutletRepository
{
    public function __construct(protected Outlet $outlet) {}

    public function all(): Collection
    {
        return Outlet::queryBuilder()->with(['user'])->get();
    }

    public function getById($id): Outlet|Model
    {
        return $this->outlet->with(['user'])->findOrFail($id);
    }

    public function save(array $data): Model|Builder
    {
        $data['user_id'] = Auth::id() ?? 1;

        return $this->outlet->query()->create($data);
    }

    public function update(array $data, Outlet $outlet): Outlet
    {
        $outlet->update($data);

        return $outlet;
    }

    public function delete(Outlet $outlet): Collection
    {
        $outlet->delete();

        return Outlet::with(
            ['user']
        )->get();
    }
}
