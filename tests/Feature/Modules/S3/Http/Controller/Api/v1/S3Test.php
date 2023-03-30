<?php

namespace Tests\Feature\Modules\S3\Http\Controller\Api\v1;

use Illuminate\Http\UploadedFile;
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
        self::markTestIncomplete('This test fails because of missing profile.jpg file.');

        $this->actingAs($this->user)
            ->getJson('/api/v1/download/profile.jpg')
            ->assertStatus(200)
            ->assertDownload('profile.jpg');
    }
}
