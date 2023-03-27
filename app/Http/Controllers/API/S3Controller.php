<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class S3Controller extends Controller
{

    public function download($filename): Response
    {
        if (!Storage::disk('s3')->exists($filename)) 
            abort(404, "File not found.");

        $file = Storage::disk('s3')->get($filename);

        $response = new Response($file, 200);
        $response->header('Content-Type', Storage::disk('s3')->mimeType($filename));
        $response->header('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }

    public function store(Request $request): JsonResponse
    {
        $file = $request->file("file");
        $filename = $file->getClientOriginalName();
        $runOn = Storage::disk("s3")->put($filename, file_get_contents($file));

        return response()->json([
            "status" => $runOn ? 200 : false,
            "message" => "file uploaded sucessfully",
            "data" => []
        ]);
    }
}
