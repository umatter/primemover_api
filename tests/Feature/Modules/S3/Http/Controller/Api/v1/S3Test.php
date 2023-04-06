<?php

namespace Tests\Feature\Modules\S3\Http\Controller\Api\v1;

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
    public function testStore(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/v1/upload', ['file' => UploadedFile::fake()->image('profile.jpg')])
            ->assertStatus(200)
            ->assertJsonStructure(['status', 'message', 'data']);
    }

    /**
     * @covers ::download
     */
    public function testDownload(): void
    {
        self::markTestIncomplete('This test has not been implemented yet.');
        Storage::fake('s3');
        $filename = 'test_file.txt';
        $fileContent = 'Hello, world!';
        Storage::disk('s3')->put($filename, $fileContent);

        $this->actingAs($this->user)
            ->get('/api/v1/download/'.$filename)
            ->assertStatus(200)
            ->assertDownload($filename);
    }
}
