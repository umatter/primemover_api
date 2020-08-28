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
        $jobs = Job::with(['behaviors', 'reports:id,job_id,path'])->latest()->get();
        return new JobResourceCollection($jobs);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
        ]);

        $data = $request->all();
        $data['user_id'] = auth()->user() ? auth()->user()->id : 1;

        $job = Job::create($data);
        return $job;
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Job $job
     * @return \Illuminate\Http\Response
     */
    public function show($id): JobResource
    {
        $job = Job::with(['behaviors'])->findOrFail($id);
        return new JobResource($job);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Job $job
     * @return \Illuminate\Http\Response
     */
    public function edit(Job $job)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Job $job
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Job $job)
    {
        /*$this->validate($request, [
            'id' => 'required',
        ]);*/
        $data = $request->all();

        $data['started_at'] = Carbon::parse($data['started_at'])->toISOString();
        $data['finished_at'] = Carbon::parse($data['finished_at'])->toISOString();

        $job->update($data);
        return $job;

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Job $job
     * @return \Illuminate\Http\Response
     */
    public function destroy(Job $job)
    {
        if ($job) {
            $job->delete();
            return Job::latest()->get();
        }
        return false;
    }
}
