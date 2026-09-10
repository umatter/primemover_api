<?php

namespace App\Modules\Agent\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Agent\Http\Request\RunnerAgentUpdateRequest;
use App\Modules\Agent\Model\Agent;
use App\Modules\Agent\Repository\AgentRepository;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Tag(
 *     name="Runner - Agents",
 *     description="Runner endpoints for updating agent status and information"
 * )
 */
class RunnerAgentController extends Controller
{
    public function __construct(protected AgentRepository $repository) {}

    /**
     * @OA\Put(
     *     path="/runner/agents/{agent}",
     *     summary="Update agent status (Runner)",
     *     description="Update agent information and status from automation runner",
     *     operationId="runnerUpdateAgent",
     *     tags={"Runner - Agents"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="agent",
     *         in="path",
     *         description="Agent ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Agent update data from runner",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 enum={"active", "inactive", "busy", "error"},
     *                 description="Current agent status",
     *                 example="active"
     *             ),
     *             @OA\Property(
     *                 property="last_activity",
     *                 type="string",
     *                 format="date-time",
     *                 description="Last activity timestamp",
     *                 example="2023-12-01T10:30:00Z"
     *             ),
     *             @OA\Property(
     *                 property="current_job_id",
     *                 type="integer",
     *                 nullable=true,
     *                 description="ID of currently running job",
     *                 example=123
     *             ),
     *             @OA\Property(
     *                 property="error_message",
     *                 type="string",
     *                 nullable=true,
     *                 description="Error message if status is error",
     *                 example="Connection timeout"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Agent updated successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Chrome Agent"),
     *                 @OA\Property(property="status", type="string", example="active"),
     *                 @OA\Property(property="last_activity", type="string", format="date-time"),
     *                 @OA\Property(property="current_job_id", type="integer", nullable=true),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Agent not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Agent not found")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="status",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="The status field is required.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function update(RunnerAgentUpdateRequest $request, Agent $agent): JsonResource
    {
        $agent = $this->repository->update($request->validated(), $agent);

        return JsonResource::make($agent);
    }
}
