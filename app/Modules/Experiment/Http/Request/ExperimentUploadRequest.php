<?php

namespace App\Modules\Experiment\Http\Request;

use Exception;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ExperimentUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'json' => ['required'],
        ];
    }

    public function getUploadedFile(): bool|string
    {
        return $this
            ->file('json')
            ->store('experiment-upload');

    }

    /**
     * @throws Exception
     */
    public function getJsonFileContent(): array
    {
        $contents = Storage::get($this->getUploadedFile());

        try {
            return json_decode($contents, true);
        } catch (Throwable $exception) {
            Log::error('1680821526: Unable to decode json', [
                'message' => $exception->getMessage(),
            ]);

            throw new Exception('Unable to decode json', 422, $exception);
        }
    }
}
