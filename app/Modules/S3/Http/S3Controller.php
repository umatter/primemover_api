<?php

namespace App\Modules\S3\Http;

use App\Http\Controllers\Controller;
use App\Modules\S3\Facade\S3Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

/**
 * @OA\Tag(
 *     name="S3",
 *     description="S3 file storage and retrieval endpoints"
 * )
 */
class S3Controller extends Controller
{
    /**
     * @OA\Get(
     *     path="/download/{filename}",
     *     summary="Download a file from the configured object store",
     *     description="Download a file from S3 storage by filename",
     *     operationId="downloadS3File",
     *     tags={"S3"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="filename",
     *         in="path",
     *         description="Name of the file to download",
     *         required=true,
     *
     *         @OA\Schema(type="string", example="document.pdf")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="File download initiated",
     *
     *         @OA\MediaType(
     *             mediaType="application/octet-stream",
     *
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="File not found"),
     *     @OA\Response(response=500, description="Error retrieving file from S3")
     * )
     */
    public function download(string $filename): Response
    {
        return S3Service::downloadFile($filename);
    }

    /**
     * @OA\Post(
     *     path="/upload",
     *     summary="Upload a file to the configured object store",
     *     description="Writes to whichever disk S3Service::getTargetDisk() selects, which is
     *     the same disk GET /download/{filename} reads from. With AWS_MOCK=true -- the
     *     setting on a deployment with no AWS account, which is this one -- that is the
     *     LOCAL disk, despite the name suggesting a test double.
     *
     *     The runner job data does NOT use this. That goes to the local MinIO through the
     *     separate `s3runner` disk, served by GET /file/{report}.",
     *     operationId="uploadFileToS3",
     *     tags={"S3"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="File to upload",
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *                 type="object",
     *                 required={"file"},
     *
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary",
     *                     description="File to upload to S3"
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="File uploaded successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="file uploaded sucessfully"),
     *             @OA\Property(property="data", type="array", @OA\Items())
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="File upload failed",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="upload file failed"),
     *             @OA\Property(property="data", type="array", @OA\Items())
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
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
     *                     property="file",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="The file field is required.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $file = $request->file('file');
        $filename = $file->getClientOriginalName();
        /*
         * The SAME disk the download half reads from. This hard-coded 's3'
         * while S3Controller::download() goes through S3Service::downloadFile(),
         * which honours getTargetDisk() -- so the two halves of one feature
         * targeted different disks and could never have seen each other's
         * files, whatever either was configured to talk to.
         */
        $runOn = Storage::disk(S3Service::getTargetDisk())->put($filename, file_get_contents($file));

        if ( ! $runOn) {
            return response()->json([
                'status' => false,
                'message' => 'upload file failed',
                'data' => [],
            ]);
        }

        return response()->json([
            'status' => 200,
            'message' => 'file uploaded sucessfully',
            'data' => [],
        ]);
    }
}
