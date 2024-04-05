<?php

namespace App\Modules\Crawler\Http\Request;

use Exception;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Throwable;

class CrawlerUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'json' => Rule::when( ! $this->getContent(), ['required']),
        ];
    }

    public function getUploadedFile(): bool|string
    {
        return $this
            ->file('json')
            ->store('crawler-upload');

    }

    /**
     * @throws Exception
     */
    public function getJsonFileContent(): ?array
    {
        try {
            return json_decode($this->getFileOrContent(), true);
        } catch (Throwable $exception) {
            Log::error('1680821526: Unable to decode json', [
                'message' => $exception->getMessage(),
            ]);

            throw new Exception('Unable to decode json', 422, $exception);
        }
    }

    public function getFileOrContent(): ?string
    {
        if ($this->getContent()) {
            return $this->getContent();
        }

        return Storage::get($this->getUploadedFile());
    }

    public function isResponseEnabled(): bool
    {
        return $this->input('response', false);
    }
}
