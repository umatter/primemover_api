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
    /**
     * @OA\Get(
     *     path="/experiments",
     *     summary="List experiments (paginated)",
     *     description="Retrieve a paginated list of experiments with pagination support",
     *     operationId="getExperiments",
     *     tags={"Experiments"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", default=1, minimum=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15, minimum=1, maximum=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Paginated list of experiments",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Test Experiment"),
     *                     @OA\Property(property="description", type="string", example="Experiment description"),
     *                     @OA\Property(property="contact", type="string", example="researcher@example.com"),
     *                     @OA\Property(property="active", type="integer", example=1),
     *                     @OA\Property(property="user_id", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="links",
     *                 type="object",
     *                 @OA\Property(property="first", type="string"),
     *                 @OA\Property(property="last", type="string"),
     *                 @OA\Property(property="prev", type="string", nullable=true),
     *                 @OA\Property(property="next", type="string", nullable=true)
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="from", type="integer"),
     *                 @OA\Property(property="last_page", type="integer"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="to", type="integer"),
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(): ResourceCollection
    {
        $experiments = ExperimentRepository::getData();

        return ResourceCollection::make($experiments->paginate());
    }

    /**
     * @OA\Get(
     *     path="/experiments/experiments-list",
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
     * @OA\Post(
     *     path="/experiments",
     *     summary="Create new experiment",
     *     description="Create a new experiment with name, description, and contact information",
     *     operationId="createExperiment",
     *     tags={"Experiments"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="My New Experiment", description="Experiment name"),
     *             @OA\Property(property="description", type="string", example="Research project description", description="Experiment description"),
     *             @OA\Property(property="contact", type="string", example="researcher@example.com", description="Contact information"),
     *             @OA\Property(property="active", type="integer", example=1, description="Active status (0 or 1)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Experiment created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="My New Experiment"),
     *                 @OA\Property(property="description", type="string", example="Research project description"),
     *                 @OA\Property(property="contact", type="string", example="researcher@example.com"),
     *                 @OA\Property(property="active", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
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
     *                     property="name",
     *                     type="array",
     *                     @OA\Items(type="string", example="The name field is required.")
     *                 )
     *             )
     *         )
     *     )
     * )
     *
     * @throws Exception
     */
    public function store(ExperimentStoreRequest $request): JsonResource
    {
        $experiment = ExperimentRepository::save($request);

        return JsonResource::make($experiment);
    }

    /**
     * @OA\Get(
     *     path="/experiments/{id}",
     *     summary="Get single experiment",
     *     description="Retrieve details of a specific experiment including associated crawlers",
     *     operationId="getExperiment",
     *     tags={"Experiments"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Experiment ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Experiment details",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Test Experiment"),
     *                 @OA\Property(property="description", type="string", example="Experiment description"),
     *                 @OA\Property(property="contact", type="string", example="researcher@example.com"),
     *                 @OA\Property(property="active", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(
     *                     property="crawlers",
     *                     type="array",
     *                     description="Associated crawlers",
     *                     @OA\Items(type="object")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Experiment not found")
     * )
     */
    public function show(Experiment $experiment): JsonResource
    {
        $this->authorize('view', $experiment);

        return JsonResource::make(ExperimentRepository::getById($experiment));
    }

    public function edit(Experiment $experiment): JsonResource
    {
        $this->authorize('view', $experiment);

        return JsonResource::make($experiment);
    }

    /**
     * @OA\Put(
     *     path="/experiments/{id}",
     *     summary="Update experiment",
     *     description="Update an existing experiment with new information",
     *     operationId="updateExperiment",
     *     tags={"Experiments"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Experiment ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Updated Experiment Name"),
     *             @OA\Property(property="description", type="string", example="Updated description"),
     *             @OA\Property(property="contact", type="string", example="newemail@example.com"),
     *             @OA\Property(property="active", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Experiment updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Updated Experiment Name"),
     *                 @OA\Property(property="description", type="string", example="Updated description"),
     *                 @OA\Property(property="contact", type="string", example="newemail@example.com"),
     *                 @OA\Property(property="active", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
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
     *                     property="name",
     *                     type="array",
     *                     @OA\Items(type="string", example="The name field is required.")
     *                 )
     *             )
     *         )
     *     )
     * )
     *
     * @throws Exception
     */
    public function update(ExperimentStoreRequest $request, Experiment $experiment): JsonResource
    {
        $this->authorize('update', $experiment);

        return JsonResource::make(ExperimentRepository::update($request, $experiment));
    }

    /**
     * @OA\Delete(
     *     path="/experiments/{id}",
     *     summary="Delete experiment (soft delete)",
     *     description="Soft delete an experiment which can be restored later",
     *     operationId="deleteExperiment",
     *     tags={"Experiments"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Experiment ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Experiment deleted successfully",
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
    public function destroy(Experiment $experiment): JsonResource
    {
        $this->authorize('delete', $experiment);

        return JsonResource::make([
            'status' => ExperimentRepository::delete($experiment),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/experiments/{experiment}/destroy",
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
        $this->authorize('delete', $experiment);

        return JsonResource::make([
            'status' => ExperimentRepository::forceDelete($experiment),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/experiments/{experiment}/upload",
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
     *     path="/experiments/upload",
     *     deprecated=true,
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
    /**
     * @OA\Post(
     *     path="/experiments/{experiment}/upload",
     *     summary="Upload experiment data",
     *     operationId="uploadExperimentDataById",
     *     tags={"Experiments"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="experiment",
     *         in="path",
     *         description="Experiment ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(property="file", type="string", format="binary"),
     *                 @OA\Property(property="content", type="string")
     *             )
     *         ),
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(property="content", type="string")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Upload successful"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Experiment not found"),
     *     @OA\Response(response=422, description="Validation error"),
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
     *     path="/experiments/{experiment}/download",
     *     summary="Download experiment data",
     *     description="Download experiment data as a file (ZIP archive)

**Curl Example:**
```bash
curl -X GET 'https://pm-api.test.primemover.example/api/v1/experiments/1/download' \
  -H 'Authorization: Bearer YOUR_TOKEN' \
  -H 'Accept: application/zip' \
  -o experiment_1_data.zip
```

**R Example:**
```r
library(httr)

response <- GET(
  url = ""https://pm-api.test.primemover.example/api/v1/experiments/1/download"",
  add_headers(
    ""Authorization"" = ""Bearer YOUR_TOKEN"",
    ""Accept"" = ""application/zip""
  )
)

# Save the file
writeBin(content(response, ""raw""), ""experiment_1_data.zip"")
print(""File downloaded successfully"")
```",
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
        $this->authorize('view', $experiment);

        return ExperimentRepository::download($experiment);
    }
}
