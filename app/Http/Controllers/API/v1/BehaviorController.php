<?php

namespace App\Http\Controllers\API\v1;

use App\Models\Behavior;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Auth;

class BehaviorController extends Controller
{
    public function index(): ResourceCollection
    {
        $behaviors = Behavior::with(['job', 'user'])->latest()->paginate(10);

        return ResourceCollection::make($behaviors);
    }

    public function store(Request $request): JsonResource
    {
        $data = $request->all();
        $data['user_id'] = Auth::id() ?? 1;

        $behavior = Behavior::query()->create($data);

        return JsonResource::make($behavior);
    }

    public function show($id): JsonResource
    {
        $behavior = Behavior::with(['user', 'job'])->findOrFail($id);

        return JsonResource::make($behavior);
    }

    public function edit(Behavior $behavior): JsonResource
    {
        return JsonResource::make($behavior);
    }

    public function update(Request $request, Behavior $behavior): JsonResource
    {
        $data = $request->all();
        $behavior->update($data);

        return JsonResource::make($behavior);
    }

    /**
     * @throws Exception
     */
    public function destroy(Behavior $behavior): ResourceCollection
    {
        $behavior->delete();

        return ResourceCollection::make(Behavior::all());
    }
}
