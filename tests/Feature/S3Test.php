<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class S3Test extends TestCase
{
    protected $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /** @test */
    public function testFileUpload()
    {
        $this->actingAs($this->user)
            ->postJson("/api/v1/upload", ["file" => UploadedFile::fake()->image('profile.jpg')])
            ->assertStatus(200)
            ->assertJsonStructure(["status", "message", "data"]);
    }

    /** @test */
    public function testFileDownload()
    {
        $this->actingAs($this->user)
            ->getJson("/api/v1/download/profile.jpg")
            ->assertStatus(200)
            ->assertDownload("profile.jpg");
    }
}
