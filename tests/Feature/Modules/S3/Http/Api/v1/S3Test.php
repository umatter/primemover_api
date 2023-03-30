<?php

namespace Tests\Feature\Modules\S3\Http\Api\v1;

use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class S3Test extends TestCase
{
    /** @test */
    public function testFileUpload(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/v1/upload', ['file' => UploadedFile::fake()->image('profile.jpg')])
            ->assertStatus(200)
            ->assertJsonStructure(['status', 'message', 'data']);
    }

    /** @test */
    public function testFileDownload(): void
    {
        self::markTestIncomplete('This test fails because of missing profile.jpg file.');

        $this->actingAs($this->user)
            ->getJson('/api/v1/download/profile.jpg')
            ->assertStatus(200)
            ->assertDownload('profile.jpg');
    }
}
