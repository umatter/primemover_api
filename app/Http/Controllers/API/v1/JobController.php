<?php

namespace App\Http\Controllers\API\v1;


use App\Job;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;


use App\Http\Resources\v1\JobResource;
use App\Http\Resources\v1\JobResourceCollection;

class JobController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request): JobResourceCollection
    {
        $keyword = $request->input('keyword');
        $jobs = Job::with(
            [
                'behaviors',
                'reports:id,job_id,path'
            ]
        )->latest()->get();

        return new JobResourceCollection($jobs);
    }

    /**
     * Show the form for creating a new resource.
     * @return JobResourceCollection
     */
    public function create(): JobResourceCollection
    {
        return new JobResourceCollection(new Job);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return JobResource
     * @throws \Illuminate\Validation\ValidationException
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

    /**
     * Display the specified resource.
     * @param $id
     * @return JobResource
     */
    public function show($id): JobResource
    {
        $job = Job::with(['behaviors'])->findOrFail($id);
        return new JobResource($job);
    }

    /**
     * Show the form for editing the specified resource.
     * @param Job $job
     * @return JobResource
     */
    public function edit(Job $job): JobResource
    {
        return new JobResource($job);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param Job $job
     * @return JobResource
     */
    public function update(Request $request, Job $job): JobResource
    {
        $data = $request->all();
        $job->update($data);
        return new JobResource($job);
    }

    /**
     * Remove the specified resource from storage.
     * @param Job $job
     * @return JobResourceCollection
     * @throws \Exception
     */
    public function destroy(Job $job): JobResourceCollection
    {
        $job->delete();
        return new JobResourceCollection(
            Job::with(['behaviors', 'reports:id,job_id,path'])->latest()->get()
        );
    }
}
