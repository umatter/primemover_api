<?php

namespace App\Modules\Job\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Job\Http\Request\RunnerJobUpdateRequest;
use App\Modules\Job\Model\Job;
use App\Modules\Job\Repository\JobRepository;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Tag(
 *     name="Runner - Jobs",
 *     description="Runner endpoints for updating job status and progress"
 * )
 */
class RunnerJobController extends Controller
{
    public function __construct(protected JobRepository $repository)
    {
    }

    /**
     * @OA\Put(
     *     path="/api/v1/runner/jobs/{job}",
     *     summary="Update job status (Runner)",
     *     description="Update job status, progress, and results from automation runner",
     *     operationId="runnerUpdateJob",
     *     tags={"Runner - Jobs"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="job",
     *         in="path",
     *         description="Job ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Job update data from runner",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="status_code",
     *                 type="string",
     *                 enum={"pending", "running", "completed", "failed", "cancelled"},
     *                 description="Current job status",
     *                 example="completed"
     *             ),
     *             @OA\Property(
     *                 property="status_message",
     *                 type="string",
     *                 nullable=true,
     *                 description="Status message or error details",
     *                 example="Job completed successfully"
     *             ),
     *             @OA\Property(
     *                 property="started_at",
     *                 type="string",
     *                 format="date-time",
     *                 nullable=true,
     *                 description="Job start timestamp",
     *                 example="2023-12-01T10:30:00Z"
     *             ),
     *             @OA\Property(
     *                 property="finished_at",
     *                 type="string",
     *                 format="date-time",
     *                 nullable=true,
     *                 description="Job completion timestamp",
     *                 example="2023-12-01T10:35:00Z"
     *             ),
     *             @OA\Property(
     *                 property="progress",
     *                 type="integer",
     *                 minimum=0,
     *                 maximum=100,
     *                 description="Job progress percentage",
     *                 example=100
     *             ),
     *             @OA\Property(
     *                 property="captcha_found",
     *                 type="boolean",
     *                 nullable=true,
     *                 description="Whether captcha was encountered",
     *                 example=false
     *             ),
     *             @OA\Property(
     *                 property="captcha_solved",
     *                 type="boolean",
     *                 nullable=true,
     *                 description="Whether captcha was solved",
     *                 example=false
     *             ),
     *             @OA\Property(
     *                 property="result_data",
     *                 type="object",
     *                 nullable=true,
     *                 description="Job execution results",
     *                 example={"pages_visited": 5, "data_collected": 150}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Job updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Web Scraping Job"),
     *                 @OA\Property(property="status_code", type="string", example="completed"),
     *                 @OA\Property(property="status_message", type="string", nullable=true),
     *                 @OA\Property(property="progress", type="integer", example=100),
     *                 @OA\Property(property="started_at", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="finished_at", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="captcha_found", type="boolean", nullable=true),
     *                 @OA\Property(property="captcha_solved", type="boolean", nullable=true),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Job not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Job not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="status_code",
     *                     type="array",
     *                     @OA\Items(type="string", example="The status code field is required.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function update(RunnerJobUpdateRequest $request, Job $job): JsonResource
    {
        return JsonResource::make($this->repository->update($request->validated(), $job));
    }
}
