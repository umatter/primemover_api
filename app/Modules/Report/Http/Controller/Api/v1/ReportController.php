<?php

namespace App\Modules\Report\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Job\Model\Job;
use App\Modules\Report\Facade\ReportRepository;
use App\Modules\Report\Http\Request\ReportStoreRequest;
use App\Modules\Report\Model\Report;
use Exception;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Response;

/**
 * @OA\Tag(
 *     name="Reports",
 *     description="Report management and file download endpoints"
 * )
 */
class ReportController extends Controller
{
    /**
     * @OA\Get(
     *     path="/reports",
     *     summary="List all reports",
     *     description="Get paginated list of reports",
     *     operationId="getReports",
     *     tags={"Reports"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of reports",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="filename", type="string", example="report.zip"),
     *                     @OA\Property(property="path", type="string", example="/reports/report.zip"),
     *                     @OA\Property(property="job_id", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(): ResourceCollection
    {
        $reports = ReportRepository::all();

        return ResourceCollection::make($reports);
    }

    /**
     * @OA\Post(
     *     path="/reports",
     *     summary="Create new report",
     *     description="Create a new report",
     *     operationId="createReport",
     *     tags={"Reports"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"filename", "job_id"},
     *             @OA\Property(property="filename", type="string", example="report.zip"),
     *             @OA\Property(property="path", type="string", example="/reports/report.zip"),
     *             @OA\Property(property="job_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Report created successfully",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(ReportStoreRequest $request): JsonResource
    {
        $report = ReportRepository::save($request->validated());

        return JsonResource::make($report);
    }

    /**
     * @OA\Get(
     *     path="/reports/{id}",
     *     summary="Get specific report",
     *     description="Get details of a specific report",
     *     operationId="getReport",
     *     tags={"Reports"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Report ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Report details",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Report not found")
     * )
     */
    public function show(Report $report): JsonResource
    {
        return JsonResource::make($report);
    }

    /**
     * @OA\Delete(
     *     path="/reports/{id}",
     *     summary="Delete report",
     *     description="Delete an existing report",
     *     operationId="deleteReport",
     *     tags={"Reports"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Report ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Report deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Report not found")
     * )
     */
    public function destroy(Report $report): bool
    {
        return ReportRepository::delete($report);
    }

    /**
     * @OA\Get(
     *     path="/file/{report}",
     *     summary="Get report file",
     *     description="Retrieve a specific report file by report ID",
     *     operationId="getReportFile",
     *     tags={"Reports"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="report",
     *         in="path",
     *         description="Report ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Report file content",
     *         @OA\MediaType(
     *             mediaType="application/octet-stream",
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Report file not found"),
     *     @OA\Response(response=500, description="Error retrieving report file")
     * )
     */
    public function getFile(Report $report): \Symfony\Component\HttpFoundation\Response
    {
        return ReportRepository::getFile($report);
    }

    /**
     * @OA\Get(
     *     path="/download/report/{job}",
     *     summary="Download job report",
     *     description="Download a ZIP archive containing the report data for a specific job",
     *     operationId="downloadJobReport",
     *     tags={"Reports"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="job",
     *         in="path",
     *         description="Job ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Report file download initiated",
     *         @OA\MediaType(
     *             mediaType="application/zip",
     *             @OA\Schema(type="string", format="binary")
     *         ),
     *         @OA\Header(
     *             header="Content-Type",
     *             description="MIME type of the downloaded file",
     *             @OA\Schema(type="string", example="application/zip")
     *         ),
     *         @OA\Header(
     *             header="Content-Disposition",
     *             description="File attachment information",
     *             @OA\Schema(type="string", example="attachment; filename=report_job_1.zip")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Job or report not found"),
     *     @OA\Response(response=500, description="Error generating report file")
     * )
     * 
     * @throws Exception
     */
    public function download(Job $job): \Symfony\Component\HttpFoundation\Response
    {
        $response = ReportRepository::download($job);
        $filename = $job->getReportZipFilename();

        if ( ! $response) {
            App::abort(404, 'Report not found or invalid');
        }

        return Response::download($response, $filename, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => "attachment; filename={$filename}",
        ])->deleteFileAfterSend();
    }
}
