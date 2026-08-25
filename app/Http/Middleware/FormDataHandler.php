<?php

namespace App\Http\Middleware;

use App\Facades\Services\JsonHelper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class FormDataHandler
{
    public const MIME_TYPES_ALLOWED = ['application/json'];

    public const DATA_KEY = 'json';

    public function handle(Request $request, Closure $next)
    {
        $formData = $this->getData($request);

        return $next($request->merge($formData));
    }

    protected function getData(Request $request): ?array
    {
        $jsonFromContent = $this->getDataFromContent($request);
        $jsonFromUploadedFile = $this->getDataFromUploadedFile($request);

        return $jsonFromUploadedFile ?? $jsonFromContent;
    }

    protected function getDataFromContent(Request $request): ?array
    {
        $content = $request->getContent();

        // A multipart upload has an empty raw body (the JSON is in the file).
        // Decoding '' throws, and JsonHelper logs that at ALERT -- one false
        // alert per experiment upload.
        if ('' === trim((string) $content)) {
            return null;
        }

        return [self::DATA_KEY => JsonHelper::jsonToArray($content)];
    }

    protected function getDataFromUploadedFile(Request $request): ?array
    {
        return Collection::make($request->allFiles())
            ->filter(fn (UploadedFile $file) => $this->isAllowedMimeType($file))
            ->map(fn (UploadedFile $file, $key) => $this->getFormDataEntry($file, $key))
            ->filter()
            ->shift();
    }

    protected function isAllowedMimeType(UploadedFile $file): bool
    {
        Log::debug('1663327438: File mime validation info', [
            'file' => [
                'mime' => $file->getMimeType(),
                'originalMime' => $file->getClientMimeType(),
                'name' => $file->getFilename(),
                'originalName' => $file->getClientOriginalName(),
                'extension' => $file->getExtension(),
                'originalExtension' => $file->getClientOriginalExtension(),
            ],
        ]);

        return in_array($file->getClientMimeType(), self::MIME_TYPES_ALLOWED);
    }

    protected function getFormDataEntry(UploadedFile $file, string $key): ?array
    {
        return [$key => JsonHelper::jsonToArray($file->getContent())];
    }
}
