<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Resources\v1\JobResource;
use App\Http\Resources\v1\JobResourceCollection;
use App\Models\Job;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class JobController extends Controller
{
    public function index(Request $request): JobResourceCollection
    {
        $keyword = $request->input('keyword');
        $jobs = Job::with(
            [
                'behaviors',
                'reports:id,job_id,path,filename',
            ]
        )->latest()->paginate(10);

        return new JobResourceCollection($jobs);
    }

    public function create(): JobResourceCollection
    {
        return new JobResourceCollection(new Job());
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request): JobResource
    {
        $data = $request->all();

        $this->validate($request, [
            'name' => 'required',
        ]);

        $data['user_id'] = auth()->user() ? auth()->user()->id : 1;

        $job = Job::create($data);

        return new JobResource($job);
    }

    public function show($id): JobResource
    {
        $job = Job::with([
            'behaviors',
            'reports:id,job_id,path,filename,created_at,updated_at',
        ])->findOrFail($id);

        return new JobResource($job);
    }

    public function edit(Job $job): JobResource
    {
        return new JobResource($job);
    }

    public function update(Request $request, Job $job): JobResource
    {
        $data = $request->all();
        $job->update($data);

        return new JobResource($job);
    }

    /**
     * @throws Exception
     */
    public function destroy(Job $job): JobResourceCollection
    {
        $job->delete();

        return new JobResourceCollection(
            Job::with(['behaviors', 'reports:id,job_id,path,filename'])->latest()->get()
        );
    }
}
