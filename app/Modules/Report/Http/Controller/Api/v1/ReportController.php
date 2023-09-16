<?php

namespace App\Modules\Report\Http\Controller\Api\v1;

use App\Facades\Factories\ZipFactory;
use App\Http\Controllers\API\v1\Controller;
use App\Modules\Job\Model\Job;
use App\Modules\Report\Model\Report;
use App\Modules\Report\Repository\ReportRepository;
use Exception;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class ReportController extends Controller
{
    public function __construct(protected ReportRepository $repository)
    {
    }

    public function index(): ResourceCollection
    {
        $reports = $this->repository->getData();

        return ResourceCollection::make($reports);
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request): JsonResource
    {
        $this->validate($request, [
            'job_id' => 'required',
            'mimetype' => 'required',
        ]);

        $report = $this->repository->save($request->all());

        return JsonResource::make($report);
    }

    public function show(Report $report): JsonResource
    {
        return JsonResource::make($report);
    }

    public function destroy(Report $report): bool
    {
        return $this->repository->delete($report);
    }

    public function download(Job $job)
    {
        foreach($job->reports()->get() as $report) {
            $path = Storage::disk('s3')->path($report->path);
            $file = Storage::disk('s3')->get($path);
            $filename = basename($path);

            Storage::disk("public")->put("report-" . $report->id . "/$filename", $file);
        }

        $zip = ZipFactory::create();
        $zipPath = storage_path('app/public/report.zip');
 
        if($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            $folderPath = storage_path('app/public/report-' . $report->id . '/');

            $files = glob($folderPath.'*');
            foreach($files as $file){
                $filename = basename($file);
                if(is_file($file)) {
                    $zip->addFile($file, $filename);
                }
            }

            $zip->close();

            array_map('unlink', glob("{$folderPath}*.*"));
            rmdir($folderPath);
        }else {
            throw new Exception('Unable to create ZIP archive');
        }

        return response()->download($zipPath, 'report-' . $report->id . '.zip', [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend();
    }
}
