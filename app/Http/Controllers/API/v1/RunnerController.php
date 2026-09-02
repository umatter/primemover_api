<?php

namespace App\Http\Controllers\API\v1;

use App\Access\Isolation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;

/**
 * The runner accounts an experiment can be assigned to.
 *
 * Runners are the service accounts (PM_SERVICE_ACCOUNT_IDS); the default one
 * (PM_DEFAULT_RUNNER_USER_ID) executes experiments whose runner_user_id is
 * NULL. Clients use this to offer names instead of raw ids.
 */
class RunnerController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/runners",
     *     summary="List runner accounts",
     *     description="The accounts an experiment's runner_user_id may point at, with the default flagged. Any authenticated user may call this.",
     *     operationId="listRunners",
     *     tags={"Experiments"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Runner accounts, ordered by id",
     *
     *         @OA\JsonContent(
     *             type="array",
     *
     *             @OA\Items(
     *                 type="object",
     *
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Administrator (ex63 runner, default)"),
     *                 @OA\Property(property="email", type="string", example="admin@primemover.example"),
     *                 @OA\Property(property="is_default", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(): JsonResponse
    {
        $default = Isolation::defaultRunnerUserId();

        $runners = User::query()
            ->whereIn('id', Isolation::serviceAccountIds())
            ->orderBy('id')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $user): array => [
                'id' => (int) $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_default' => (int) $user->id === $default,
            ])
            ->values();

        return response()->json($runners);
    }
}
