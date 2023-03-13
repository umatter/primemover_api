<?php

namespace App\Modules\Outlet\Http\Service;

use App\Modules\Outlet\Model\Outlet;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class OutletService
{
    protected Outlet $outlet;

    public function __construct(Outlet $outlet)
    {
        $this->outlet = $outlet;
    }
    
    public function getData(): Collection
    {
        $outlets = $this->outlet->with(['user'])->latest()->get();
        
        return $outlets;
    }
    
    public function getById($id): Outlet
    {
        return $this->outlet->with(['user'])->findOrFail($id);
    }

    public function save(array $data)
    {
        $data = $data;
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
        )->latest()->get();
        
    }
    
}