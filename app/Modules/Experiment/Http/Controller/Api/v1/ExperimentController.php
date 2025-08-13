<?php

namespace App\Modules\Experiment\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Experiment\Facade\ExperimentRepository;
use App\Modules\Experiment\Http\Request\ExperimentStoreRequest;
use App\Modules\Experiment\Http\Request\ExperimentUploadRequest;
use App\Modules\Experiment\Model\Experiment;
use Exception;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;

/**
 * @OA\Tag(
 *     name="Experiments",
 *     description="Experiment management and data handling endpoints"
 * )
 */
class ExperimentController extends Controller
{
    public function index(): ResourceCollection
    {
        $experiments = ExperimentRepository::getData();

        return ResourceCollection::make($experiments->paginate());
    }

    /**
     * @OA\Get(
     *     path="/api/v1/experiments/experiments-list",
     *     summary="Get all experiments list",
     *     description="Retrieve a complete list of all experiments without pagination

**Curl Example:**
```bash
curl -X GET 'https://pm-api.test.primemover.example/api/v1/experiments/experiments-list' \
  -H 'Authorization: Bearer YOUR_TOKEN' \
  -H 'Accept: application/json'
```

**R Example:**
```r
library(httr)

response <- GET(
  url = ""https://pm-api.test.primemover.example/api/v1/experiments/experiments-list"",
  add_headers(
    ""Authorization"" = ""Bearer YOUR_TOKEN"",
    ""Accept"" = ""application/json""
  )
)

experiments_data <- content(response, ""parsed"")
print(experiments_data)
```",
     *     operationId="getAllExperimentsList",
     *     tags={"Experiments"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Complete list of experiments",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Test-Experiment 1"),
     *                     @OA\Property(property="description", type="string", example="Lorem ipsum dolor sit amet"),
     *                     @OA\Property(property="contact", type="string", example="Nobody"),
     *                     @OA\Property(property="active", type="integer", example=1),
     *                     @OA\Property(property="updated_at", type="string", format="date-time"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="user_id", type="integer", example=2)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function all(): ResourceCollection
    {
        $experiments = ExperimentRepository::all();

        return ResourceCollection::make($experiments);
    }

    /**
     * @throws Exception
     */
    public function store(ExperimentStoreRequest $request): JsonResource
    {
        $experiment = ExperimentRepository::save($request);

        return JsonResource::make($experiment);
    }

    public function show(Experiment $experiment): JsonResource
    {
        return JsonResource::make(ExperimentRepository::getById($experiment));
    }

    public function edit(Experiment $experiment): JsonResource
    {
        return JsonResource::make($experiment);
    }

    /**
     * @throws Exception
     */
    public function update(ExperimentStoreRequest $request, Experiment $experiment): JsonResource
    {
        return JsonResource::make(ExperimentRepository::update($request, $experiment));
    }

    /**
     * @throws Exception
     */
    public function destroy(Experiment $experiment): JsonResource
    {
        return JsonResource::make([
            'status' => ExperimentRepository::delete($experiment),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/experiments/{experiment}/destroy",
     *     summary="Force delete experiment",
     *     description="Permanently delete an experiment and all associated data",
     *     operationId="forceDeleteExperiment",
     *     tags={"Experiments"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="experiment",
     *         in="path",
     *         description="Experiment ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Experiment force deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="status", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Experiment not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     * 
     * @throws Exception
     */
    public function forceDelete(Experiment $experiment): JsonResource
    {
        return JsonResource::make([
            'status' => ExperimentRepository::forceDelete($experiment),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/experiments/{experiment}/upload",
     *     summary="Upload experiment configuration",
     *     description="Upload configuration data or file to an experiment

**Curl Example (with file):**
```bash
curl -X POST 'https://pm-api.test.primemover.example/api/v1/experiments/1/upload' \
  -H 'Authorization: Bearer YOUR_TOKEN' \
  -H 'Accept: application/json' \
  -F 'file=@config.json'
```

**Curl Example (with JSON content):**
```bash
curl -X POST 'https://pm-api.test.primemover.example/api/v1/experiments/1/upload' \
  -H 'Authorization: Bearer YOUR_TOKEN' \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{
    ""content"": ""{\\""name\\"":\\""Test Config\\"",\\""active\\""1}""
  }'
```

**R Example (with file):**
```r
library(httr)

response <- POST(
  url = ""https://pm-api.test.primemover.example/api/v1/experiments/1/upload"",
  add_headers(
    ""Authorization"" = ""Bearer YOUR_TOKEN"",
    ""Accept"" = ""application/json""
  ),
  body = list(file = upload_file(""config.json"")),
  encode = ""multipart""
)

config_result <- content(response, ""parsed"")
print(config_result)
```",
     *     operationId="uploadExperimentConfig",
     *     tags={"Experiments"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="experiment",
     *         in="path",
     *         description="Experiment ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Upload experiment configuration data or file",
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary",
     *                     description="Configuration file to upload"
     *                 ),
     *                 @OA\Property(
     *                     property="content",
     *                     type="string",
     *                     description="JSON configuration content as string"
     *                 )
     *             )
     *         ),
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="content",
     *                     type="string",
     *                     description="JSON configuration content"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Configuration uploaded successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="crawlers",
     *                     type="array",
     *                     description="Updated crawlers with configuration",
     *                     @OA\Items(type="object")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Experiment not found"),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="file",
     *                     type="array",
     *                     @OA\Items(type="string", example="The file field is required when content is not present.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    /**
     * @OA\Post(
     *     path="/api/v1/experiments/upload",
     *     summary="Upload experiment data",
     *     description="Upload experiment configuration data or file (general upload)

**Curl Example (with file):**
```bash
curl -X POST 'https://pm-api.test.primemover.example/api/v1/experiments/upload' \
  -H 'Authorization: Bearer YOUR_TOKEN' \
  -H 'Accept: application/json' \
  -F 'file=@experiment_data.json'
```

**Curl Example (with JSON content):**
```bash
curl -X POST 'https://pm-api.test.primemover.example/api/v1/experiments/upload' \
  -H 'Authorization: Bearer YOUR_TOKEN' \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{
    ""content"": ""{\\""name\\"":\\""Test Experiment\\"",\\""active\\""1}""
  }'
```

**R Example (with file):**
```r
library(httr)

response <- POST(
  url = ""https://pm-api.test.primemover.example/api/v1/experiments/upload"",
  add_headers(
    ""Authorization"" = ""Bearer YOUR_TOKEN"",
    ""Accept"" = ""application/json""
  ),
  body = list(file = upload_file(""experiment_data.json"")),
  encode = ""multipart""
)

upload_result <- content(response, ""parsed"")
print(upload_result)
```",
     *     operationId="uploadExperimentData",
     *     tags={"Experiments"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Upload experiment data or file",
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary",
     *                     description="Experiment data file to upload"
     *                 ),
     *                 @OA\Property(
     *                     property="content",
     *                     type="string",
     *                     description="JSON experiment data as string"
     *                 )
     *             )
     *         ),
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="content",
     *                     type="string",
     *                     description="JSON experiment data"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Experiment data uploaded successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="status", type="string", example="success"),
     *                 @OA\Property(property="message", type="string", example="Experiment data uploaded successfully"),
     *                 @OA\Property(property="uploaded_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="file",
     *                     type="array",
     *                     @OA\Items(type="string", example="The file field is required when content is not present.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function upload(ExperimentUploadRequest $request, Experiment $experiment = null): JsonResource
    {
        $uploadResult = ExperimentRepository::upload($request, $experiment);

        if ($request->isResponseEnabled()) {
            return JsonResource::make([
                'crawlers' => Crawler::query()
                    ->where('updated', 1)
                    ->with(['proxy', 'agent.preferences', 'configuration.preferences', 'queuesNew'])
                    ->get(),
            ]);
        }

        return JsonResource::make($uploadResult);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/experiments/{experiment}/download",
     *     summary="Download experiment data",
     *     description="Download experiment data as a file (ZIP archive)",
     *     operationId="downloadExperimentData",
     *     tags={"Experiments"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="experiment",
     *         in="path",
     *         description="Experiment ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="File download initiated",
     *         @OA\MediaType(
     *             mediaType="application/zip",
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Experiment not found or no data available"),
     *     @OA\Response(response=500, description="Error generating download file")
     * )
     */
    public function download(Experiment $experiment): Response
    {
        return ExperimentRepository::download($experiment);
    }
}
