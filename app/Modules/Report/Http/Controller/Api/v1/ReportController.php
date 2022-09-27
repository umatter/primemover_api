<?php

namespace App\Modules\Report\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Job\Model\Job;
use App\Modules\Report\Model\Report;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class ReportController extends Controller
{
    public function index(): ResourceCollection
    {
        $reports = Report::query()->latest()->paginate(10);

        return ResourceCollection::make($reports);
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request): Response
    {
        $this->validate($request, [
            'job_id' => 'required',
            'mimetype' => 'required',
        ]);

        $env = env('APP_ENV');
        $dev = env('APP_DEV_API_URL');
        $prod = env('APP_PROD_API_URL');
        $path = 'local' === $env ? $dev : $prod;

        $job = Job::findOrFail($request->job_id);
        $desc = 'Bot: ' . $job->queue->crawler->name . ' | Job-Name: ' . $job->name . ', Job-ID: ' . $job->id . ', Job-Type: ' . $job->type . ')';

        $data['job_id'] = $request->job_id;
        $data['description'] = ($request->description) ? $request->description : $desc;
        $data['user_id'] = auth()->user() ? auth()->user()->id : 1;
        $data['filecontent'] = $request->filecontent;
        $data['filename'] = $request->filename;
        $data['path'] = $request->path;
        $data['mimetype'] = $request->mimetype;

        // $this->storeFile();

        $report = Report::create($data);
        if ($report && $request->filecontent) {
            $report->path = $path . '/file/' . $report->id;
        }
        $report->save();

        return $report;
    }

    public function show(Report $report): JsonResource
    {
        return JsonResource::make($report);
    }

    public function destroy(Report $report): bool
    {
        return $report->delete();
    }
}
