<?php

namespace App\Modules\Job\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Job\Http\Request\StoreJobRequest;
use App\Modules\Job\Http\Request\UpdateJobRequest;
use App\Modules\Job\Model\Job;
use App\Modules\Job\Repository\JobRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Tag(
 *     name="Jobs",
 *     description="Job management endpoints"
 * )
 */
class JobController extends Controller
{
    public function __construct(protected JobRepository $repository)
    {
    }

    /**
     * @OA\Get(
     *     path="/jobs",
     *     summary="List all jobs",
     *     description="Get paginated list of jobs",
     *     operationId="getJobs",
     *     tags={"Jobs"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="queue",
     *         in="query",
     *         description="Filter by queue ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="filter[recent]",
     *         in="query",
     *         description="Filter recent jobs (days)",
     *         required=false,
     *         @OA\Schema(type="integer", example=30)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of jobs",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Job Name"),
     *                     @OA\Property(property="description", type="string", example="Job description"),
     *                     @OA\Property(property="type", type="string", example="visit_webpage_browserbar"),
     *                     @OA\Property(property="status_code", type="string", example="pending"),
     *                     @OA\Property(property="queue_id", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(Request $request): ResourceCollection
    {
        $queueId = $request->input('queue');
        $jobs = $this->repository->all($queueId);

        return ResourceCollection::make($jobs);
    }

    /**
     * @OA\Post(
     *     path="/jobs",
     *     summary="Create new job",
     *     description="Create a new job",
     *     operationId="createJob",
     *     tags={"Jobs"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "queue_id"},
     *             @OA\Property(property="name", type="string", example="Job Name"),
     *             @OA\Property(property="description", type="string", example="Job description"),
     *             @OA\Property(property="type", type="string", example="visit_webpage_browserbar"),
     *             @OA\Property(property="queue_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Job created successfully",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreJobRequest $request): JsonResource
    {
        $job = $this->repository->save($request->validated());

        return JsonResource::make($job);
    }

    /**
     * @OA\Get(
     *     path="/jobs/{id}",
     *     summary="Get specific job",
     *     description="Get details of a specific job",
     *     operationId="getJob",
     *     tags={"Jobs"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Job ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Job details",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Job not found")
     * )
     */
    public function show($id): JsonResource
    {
        $job = $this->repository->getById($id);

        return JsonResource::make($job);
    }

    public function edit(Job $job): JsonResource
    {
        return JsonResource::make($job);
    }

    /**
     * @OA\Put(
     *     path="/jobs/{id}",
     *     summary="Update job",
     *     description="Update an existing job",
     *     operationId="updateJob",
     *     tags={"Jobs"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Job ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="type", type="string"),
     *             @OA\Property(property="queue_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Job updated successfully",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Job not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(UpdateJobRequest $request, Job $job): JsonResource
    {
        return JsonResource::make($this->repository->update($request->validated(), $job));
    }

    /**
     * @OA\Delete(
     *     path="/jobs/{id}",
     *     summary="Delete job",
     *     description="Delete an existing job",
     *     operationId="deleteJob",
     *     tags={"Jobs"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Job ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Job deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Job not found")
     * )
     * 
     * @throws Exception
     */
    public function destroy(Job $job): ResourceCollection
    {
        $this->repository->delete($job);

        return ResourceCollection::make(
            Job::with(['behaviors', 'reports:id,job_id,path,filename'])->get()
        );
    }
}
