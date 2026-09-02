<?php

namespace Tests\Feature\Isolation;

use App\Modules\Experiment\Model\Experiment;
use Illuminate\Support\Facades\Gate;

/**
 * Isolation ON: ownership is derived from the experiment, never read per row.
 *
 * This is the case the whole design exists for. Every crawler, run, job and
 * report in the production database carries the user_id of whoever ingested it
 * -- the runner or the upload pipeline -- not of the researcher who owns the
 * study. A per-row user_id check would therefore show a researcher an
 * experiment containing no bots, and no backfill fixes it while ingest keeps
 * stamping the uploader.
 *
 * The two halves below are the pair that per-row ownership gets wrong in both
 * directions: rows stamped with somebody else must be VISIBLE when the
 * experiment is the caller's, and rows stamped with the caller must be
 * INVISIBLE when the experiment is not.
 *
 * @internal
 *
 * @coversDefaultClass \App\Models\Concerns\ScopesToOwner
 */
class DerivedOwnershipTest extends IsolationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->enableIsolation();
        $this->actingAs($this->researcher);
    }

    /**
     * @dataProvider derivedModels
     */
    public function test_fixture_stamps_child_rows_with_somebody_else(string $key, string $model): void
    {
        $this->assertSame(
            (int) $this->serviceAccount->id,
            (int) $this->mine[$key]->user_id,
            "This suite only proves anything if the researcher's {$model} is stamped with another user."
        );

        $this->assertNotSame((int) $this->researcher->id, (int) $this->mine[$key]->user_id);
    }

    /**
     * @dataProvider derivedModels
     *
     * @covers ::scopeVisibleTo
     */
    public function test_row_stamped_with_another_user_is_visible_through_the_owned_experiment(string $key, string $model): void
    {
        $this->assertContains(
            (int) $this->mine[$key]->id,
            $this->visibleIds($model),
            "A {$model} under the researcher's own experiment must be visible even though its user_id is somebody else's."
        );

        $this->assertTrue(Gate::allows('view', $this->mine[$key]));
        $this->assertTrue(Gate::allows('update', $this->mine[$key]));
    }

    /**
     * The mirror image: a row carrying the caller's own user_id, sitting under
     * somebody else's experiment. Per-row ownership would hand it over.
     *
     * @dataProvider derivedModels
     *
     * @covers ::scopeVisibleTo
     */
    public function test_row_stamped_with_the_caller_stays_invisible_under_another_users_experiment(string $key, string $model): void
    {
        $planted = $this->treeFor($this->otherResearcher, $this->researcher->id);

        $this->assertSame((int) $this->researcher->id, (int) $planted[$key]->user_id);

        $this->assertNotContains(
            (int) $planted[$key]->id,
            $this->visibleIds($model),
            "A {$model} stamped with the researcher must stay invisible while its experiment belongs to someone else."
        );

        $this->assertTrue(Gate::denies('view', $planted[$key]));
        $this->assertTrue(Gate::denies('update', $planted[$key]));
    }

    /**
     * The experiment is the root, so there it IS the row's own user_id.
     *
     * @covers ::scopeVisibleTo
     */
    public function test_experiment_ownership_is_read_directly_from_the_row(): void
    {
        $this->assertSame('', Experiment::OWNERSHIP_PATH);

        $this->assertSame(
            [(int) $this->mine['experiment']->id],
            $this->visibleIds(Experiment::class)
        );
    }
}
