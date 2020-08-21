<?php

namespace App\Http\Controllers\API\v1;


use App\Report;
use Illuminate\Http\Request;
use Illuminate\Http\Response;


use App\Http\Resources\v1\ReportResource;
use App\Http\Resources\v1\ReportResourceCollection;
use Illuminate\Support\Facades\File;

class ReportController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request): ReportResourceCollection
    {
        $keyword = $request->input('keyword');
        $reports = Report::latest()->get();
        return new ReportResourceCollection($reports);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'description' => 'required',
            'job_id' => 'required',
            'filecontent' => 'required',
            'mimetype' => 'required',
        ]);


        $data['job_id'] = $request->job_id;
        $data['description'] = $request->description;
        $data['user_id'] = auth()->user() ? auth()->user()->id : 1;
        $data['filecontent'] = $request->filecontent;
        $data['mimetype'] = $request->mimetype;

        //$this->storeFile();

        $report = Report::create($data);
        return $report;
    }

    public function getFile($report_id)
    {
        $report = Report::findOrFail($report_id);
        return response(base64_decode($report->filecontent))->header('Content-Type', $report->mimetype);

    }


    /**
     * Display the specified resource.
     */
    public function storeFile($data, $request)
    {
        $path = public_path() . '/reports/' . $data['job_id'];

        if (!File::exists($path)) {
            File::makeDirectory($path, $mode = 0777, true, true);
        }

        $path = File::exists($path) ? $path : public_path();

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = $file->getClientOriginalName();
            $data['filename'] = $filename;
            $file->move($path, $filename);
            $data['path'] = url('/reports/' . $data['job_id'] . '/' . $filename);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Report $report
     * @return \Illuminate\Http\Response
     */
    public function show($id): ReportResource
    {
        $report = Report::findOrFail($id);
        return new ReportResource($report);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Report $report
     * @return \Illuminate\Http\Response
     */
    public function edit(Report $report)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Report $report
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Report $report)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Report $report
     * @return \Illuminate\Http\Response
     */
    public function destroy(Report $report)
    {
        if ($report) {
            $report->delete();
            return Report::latest()->get();
        }
        return false;
    }
}
