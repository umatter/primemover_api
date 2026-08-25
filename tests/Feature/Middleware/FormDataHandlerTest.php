<?php

namespace Tests\Feature\Middleware;

use App\Http\Middleware\FormDataHandler;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Http\Middleware\FormDataHandler
 */
class FormDataHandlerTest extends TestCase
{
    /**
     * A multipart upload carries its JSON in the file and has an empty raw
     * body. Decoding that body used to throw and log an ALERT on every
     * experiment upload.
     *
     * @covers ::getDataFromContent
     */
    public function test_multipart_upload_does_not_alert_on_the_empty_body(): void
    {
        Log::spy();

        $file = UploadedFile::fake()->createWithContent('scenario.json', '[{"name":"bot"}]');
        $request = Request::create('/upload', 'POST', [], [], ['json' => $file], ['CONTENT_TYPE' => 'multipart/form-data']);
        $request->files->set('json', new UploadedFile($file->getPathname(), 'scenario.json', 'application/json', null, true));

        $merged = (new FormDataHandler())->handle($request, fn (Request $r) => $r);

        $this->assertSame([['name' => 'bot']], $merged->input('json'));
        Log::shouldNotHaveReceived('alert');
    }

    /**
     * @covers ::getDataFromContent
     */
    public function test_json_body_is_still_decoded(): void
    {
        Log::spy();

        $request = Request::create('/upload', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], '{"a":1}');

        $merged = (new FormDataHandler())->handle($request, fn (Request $r) => $r);

        $this->assertSame(['a' => 1], $merged->input(FormDataHandler::DATA_KEY));
        Log::shouldNotHaveReceived('alert');
    }
}
