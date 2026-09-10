<?php

namespace App\Http\Controllers\API\v1;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Artisan;

/**
 * @OA\Info(
 *     title="Primemover API",
 *     version="1.0.0",
 *     description="Web automation platform API for managing browser automation experiments",
 *
 *     @OA\Contact(
 *         email="support@primemover.example"
 *     )
 * )
 *
 * @OA\Server(
 *     url="https://primemover.qamelab.org/api/v1",
 *     description="ex63 (tailnet only; same origin as the GUI, so no CORS)"
 * )
 * @OA\Server(
 *     url="http://localhost:8090/api/v1",
 *     description="Local -- 8090 is PM_API_HTTP_PORT, not Laravel's default 8080"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter JWT Bearer token"
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="Authentication and authorization endpoints"
 * )
 * @OA\Tag(
 *     name="Experiments",
 *     description="Experiment management endpoints"
 * )
 * @OA\Tag(
 *     name="Agents",
 *     description="Browser agent management endpoints"
 * )
 * @OA\Tag(
 *     name="Crawlers",
 *     description="Crawler management endpoints"
 * )
 * @OA\Tag(
 *     name="Queues",
 *     description="Queue and job management endpoints"
 * )
 * @OA\Tag(
 *     name="Files",
 *     description="File upload and download endpoints"
 * )
 * @OA\Tag(
 *     name="Development",
 *     description="Development and testing endpoints"
 * )
 * @OA\Tag(
 *     name="Utilities",
 *     description="Utility endpoints"
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    /**
     * @OA\Get(
     *     path="/dev/truncate-data",
     *     summary="Truncate development data",
     *     description="Clears development data (development only)",
     *     tags={"Development"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=204,
     *         description="Data truncated successfully"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function truncateData(): Response
    {
        Artisan::call('app:truncate-data');

        return \Illuminate\Support\Facades\Response::noContent();
    }
}
