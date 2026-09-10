<?php

namespace Tests\Feature\Modules\Experiment;

use App\Models\User;
use App\Modules\Experiment\Model\DTO\DownloadData;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Experiment\Pipe\Download\StoreCreatedZipArchive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

/**
 * A failed write must fail at the write.
 *
 * The download pipeline creates a zip, stores it on the `s3` disk, and then
 * reads it straight back to serve it. `Storage::put()` returns FALSE on a disk
 * it cannot write to -- it does not throw -- and that result was logged and
 * ignored, so the pipeline carried on and the failure surfaced two steps later
 * as `phpseclib3\Exception\FileNotFoundException: File not found.` from the
 * read.
 *
 * That is the error `GET /experiments/{id}/download` has been answering on
 * ex63: it names neither the disk nor the write, and points at the wrong half
 * of the operation. The underlying cause there is that the `s3` disk has never
 * been configured -- AWS_BUCKET holds an ARN, and until this change the disk
 * had no `endpoint` key at all, the same omission `s3runner` had -- but a
 * misleading error would outlive any one misconfiguration.
 *
 * @internal
 *
 * @coversDefaultClass \App\Modules\Experiment\Pipe\Download\StoreCreatedZipArchive
 */
class ExperimentDownloadWriteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @covers ::handle
     */
    public function test_a_rejected_write_throws_naming_the_disk(): void
    {
        Storage::shouldReceive('disk')->andReturnSelf();
        Storage::shouldReceive('put')->andReturnFalse();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Could not write the export archive/');

        (new StoreCreatedZipArchive())->handle($this->downloadData(), fn ($passable) => $passable);
    }

    /**
     * @covers ::handle
     */
    public function test_the_message_says_the_write_failed_not_that_a_file_is_missing(): void
    {
        // The whole point: "File not found" sent whoever saw it looking at the
        // read, or at a missing experiment, rather than at an unwritable disk.
        Storage::shouldReceive('disk')->andReturnSelf();
        Storage::shouldReceive('put')->andReturnFalse();

        try {
            (new StoreCreatedZipArchive())->handle($this->downloadData(), fn ($passable) => $passable);
            $this->fail('expected a RuntimeException');
        } catch (RuntimeException $e) {
            $this->assertStringNotContainsString('File not found', $e->getMessage());
            $this->assertStringContainsString('disk', $e->getMessage());
            $this->assertStringContainsString('bucket', $e->getMessage());
        }
    }

    private function downloadData(): DownloadData
    {
        $user = User::factory()->create();
        $experiment = Experiment::factory()->create(['user_id' => $user->id]);

        $zip = tempnam(sys_get_temp_dir(), 'export').'.zip';
        file_put_contents($zip, 'not really a zip, and it does not need to be');

        $data = DownloadData::from(compact('experiment'));
        $data->setPathZipArchive($zip);
        $data->setPathUploadS3('api-data/experiments/test.zip');

        return $data;
    }
}
