<?php

namespace App\Http\Controllers\API\v1;

use App\Models\Job;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class JobController extends Controller
{
    public function index(Request $request): ResourceCollection
    {
        $queueId = $request->input('queue');
        $jobs = Job::query()
            ->when($queueId, fn (Builder $query) => $query->where('queue_id', $queueId))
            ->with(['queue'])
            ->latest()
            ->paginate(10);

        return ResourceCollection::make($jobs);
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request): JsonResource
    {
        $data = $request->all();

        $this->validate($request, [
            'name' => 'required',
        ]);

        $data['user_id'] = Auth::id() ?? 1;

        $job = Job::query()->create($data);

        return JsonResource::make($job);
    }

    public function show($id): JsonResource
    {
        $job = Job::with([
            'behaviors',
            'reports:id,job_id,path,filename,created_at,updated_at',
        ])->findOrFail($id);

        return JsonResource::make($job);
    }

    public function edit(Job $job): JsonResource
    {
        return JsonResource::make($job);
    }

    public function update(Request $request, Job $job): JsonResource
    {
        $data = $request->all();
        $job->update($data);

        return JsonResource::make($job);
    }

    /**
     * @throws Exception
     */
    public function destroy(Job $job): ResourceCollection
    {
        $job->delete();

        return ResourceCollection::make(
            Job::with(['behaviors', 'reports:id,job_id,path,filename'])->latest()->get()
        );
    }
}
