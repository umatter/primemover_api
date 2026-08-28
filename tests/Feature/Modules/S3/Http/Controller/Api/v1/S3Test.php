<?php

namespace Tests\Feature\Modules\S3\Http\Controller\Api\v1;

use App\Modules\S3\Http\S3Controller;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Modules\S3\Http\S3Controller
 */
class S3Test extends TestCase
{
    /**
     * @covers ::store
     */
    public function test_store(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/v1/upload', ['file' => UploadedFile::fake()->image('profile.jpg')])
            ->assertStatus(200)
            ->assertJsonStructure(['status', 'message', 'data']);
    }

    /**
     * @covers ::download
     */
    public function test_download(): void
    {
        Storage::fake('s3');
        $filename = 'test_file.txt';
        $fileContent = 'Hello, world!';
        Storage::disk('s3')->put($filename, $fileContent);

        $controller = new S3Controller();
        $result = $controller->download($filename);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals($fileContent, $result->getContent());
        $this->assertEquals(200, $result->getStatusCode());

        $this->actingAs($this->user)
            ->get('/api/v1/download/'.$filename)
            ->assertStatus(200)
            ->assertDownload($filename);
    }
}
