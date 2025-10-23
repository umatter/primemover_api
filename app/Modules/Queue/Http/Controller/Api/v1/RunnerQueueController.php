<?php

namespace App\Modules\Queue\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Queue\Http\Request\RunnerQueueUpdateRequest;
use App\Modules\Queue\Model\Queue;
use App\Modules\Queue\Repository\QueueRepository;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Tag(
 *     name="Runner - Queues",
 *     description="Runner endpoints for updating queue status and progress"
 * )
 */
class RunnerQueueController extends Controller
{
    public function __construct(protected QueueRepository $repository)
    {
    }

    /**
     * @OA\Put(
     *     path="/api/v1/runner/queues/{queue}",
     *     summary="Update queue status (Runner)",
     *     description="Update queue status, progress, and execution details from automation runner",
     *     operationId="runnerUpdateQueue",
     *     tags={"Runner - Queues"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="queue",
     *         in="path",
     *         description="Queue ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Queue update data from runner",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="status_code",
     *                 type="string",
     *                 enum={"pending", "running", "completed", "failed", "cancelled"},
     *                 description="Current queue status",
     *                 example="running"
     *             ),
     *             @OA\Property(
     *                 property="status_message",
     *                 type="string",
     *                 nullable=true,
     *                 description="Status message or error details",
     *                 example="Processing jobs in queue"
     *             ),
     *             @OA\Property(
     *                 property="processing",
     *                 type="boolean",
     *                 description="Whether queue is currently processing",
     *                 example=true
     *             ),
     *             @OA\Property(
     *                 property="processed",
     *                 type="boolean",
     *                 description="Whether queue has been processed",
     *                 example=false
     *             ),
     *             @OA\Property(
     *                 property="reviewed",
     *                 type="boolean",
     *                 description="Whether queue has been reviewed",
     *                 example=false
     *             ),
     *             @OA\Property(
     *                 property="started_at",
     *                 type="string",
     *                 format="date-time",
     *                 nullable=true,
     *                 description="Queue start timestamp",
     *                 example="2023-12-01T10:30:00Z"
     *             ),
     *             @OA\Property(
     *                 property="finished_at",
     *                 type="string",
     *                 format="date-time",
     *                 nullable=true,
     *                 description="Queue completion timestamp",
     *                 example="2023-12-01T11:30:00Z"
     *             ),
     *             @OA\Property(
     *                 property="progress_percentage",
     *                 type="integer",
     *                 minimum=0,
     *                 maximum=100,
     *                 description="Queue progress percentage",
     *                 example=75
     *             ),
     *             @OA\Property(
     *                 property="jobs_completed",
     *                 type="integer",
     *                 description="Number of jobs completed",
     *                 example=3
     *             ),
     *             @OA\Property(
     *                 property="jobs_failed",
     *                 type="integer",
     *                 description="Number of jobs failed",
     *                 example=0
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Queue updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Data Collection Queue"),
     *                 @OA\Property(property="status_code", type="string", example="running"),
     *                 @OA\Property(property="status_message", type="string", nullable=true),
     *                 @OA\Property(property="processing", type="boolean", example=true),
     *                 @OA\Property(property="processed", type="boolean", example=false),
     *                 @OA\Property(property="reviewed", type="boolean", example=false),
     *                 @OA\Property(property="started_at", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="finished_at", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="progress_percentage", type="integer", example=75),
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
     *         description="Queue not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Queue not found")
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
    public function update(RunnerQueueUpdateRequest $request, Queue $queue): JsonResource
    {
        return JsonResource::make($this->repository->update($request->validated(), $queue));
    }
}
