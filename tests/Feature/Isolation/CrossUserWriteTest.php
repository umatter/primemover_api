<?php

namespace Tests\Feature\Isolation;

use App\Modules\Crawler\Model\Crawler;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Queue\Model\Queue;
use App\Rules\OwnedParent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidatorInstance;

/**
 * Isolation ON: one researcher cannot write to another researcher's rows.
 *
 * Two separate boundaries are covered. The policies stop a researcher acting on
 * a row they do not own. The OwnedParent rule stops the subtler move: keeping
 * your own row and re-pointing it at someone else's experiment. Because
 * ownership is derived, re-parenting a crawler hands over the whole subtree of
 * runs, jobs and reports underneath it in a single PUT.
 *
 * @internal
 *
 * @coversDefaultClass \App\Rules\OwnedParent
 */
class CrossUserWriteTest extends IsolationTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->enableIsolation();
        $this->actingAs($this->researcher);
    }

    /**
     * @dataProvider ownedModels
     */
    public function testUpdateIsDeniedOnAnotherResearchersRow(string $key, string $model): void
    {
        $this->assertTrue(
            Gate::denies('update', $this->theirs[$key]),
            "A researcher must not be able to update another user's {$model}."
        );

        $this->assertTrue(
            Gate::allows('update', $this->mine[$key]),
            "A researcher must still be able to update their own {$model}."
        );
    }

    /**
     * @dataProvider ownedModels
     */
    public function testDeleteIsDeniedOnAnotherResearchersRow(string $key, string $model): void
    {
        $this->assertTrue(
            Gate::denies('delete', $this->theirs[$key]),
            "A researcher must not be able to delete another user's {$model}."
        );

        $this->assertTrue(
            Gate::allows('delete', $this->mine[$key]),
            "A researcher must still be able to delete their own {$model}."
        );
    }

    /**
     * @covers ::validate
     */
    public function testOwnedParentRejectsAnExperimentTheCallerDoesNotOwn(): void
    {
        $validator = $this->validateParent('experiment_id', $this->theirs['experiment']->id, Experiment::class);

        $this->assertTrue($validator->fails(), 'Re-parenting into another user\'s experiment must be rejected.');

        // Phrased as "not found" on purpose: a "forbidden" message would
        // confirm that the id exists and belongs to somebody else.
        $this->assertStringContainsString(
            'could not be found',
            $validator->errors()->first('experiment_id')
        );
    }

    /**
     * @covers ::validate
     */
    public function testOwnedParentAcceptsAnExperimentTheCallerOwns(): void
    {
        $validator = $this->validateParent('experiment_id', $this->mine['experiment']->id, Experiment::class);

        $this->assertTrue($validator->passes(), 'A researcher must still be able to name their own experiment.');
    }

    /**
     * The same guard is applied to crawler_id on a run and queue_id on a job.
     *
     * @covers ::validate
     */
    public function testOwnedParentGuardsTheDerivedParentsToo(): void
    {
        $this->assertTrue($this->validateParent('crawler_id', $this->theirs['crawler']->id, Crawler::class)->fails());
        $this->assertTrue($this->validateParent('crawler_id', $this->mine['crawler']->id, Crawler::class)->passes());

        $this->assertTrue($this->validateParent('queue_id', $this->theirs['queue']->id, Queue::class)->fails());
        $this->assertTrue($this->validateParent('queue_id', $this->mine['queue']->id, Queue::class)->passes());
    }

    /**
     * @covers ::validate
     */
    public function testReparentingACrawlerOverHttpIsRejectedAndChangesNothing(): void
    {
        $this->withExceptionHandling();

        $this->actingAs($this->researcher)
            ->putJson('/api/v1/crawlers/'.$this->mine['crawler']->id, [
                'name' => 'stolen',
                'experiment_id' => $this->theirs['experiment']->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('experiment_id');

        $this->assertDatabaseHas('crawlers', [
            'id' => $this->mine['crawler']->id,
            'experiment_id' => $this->mine['experiment']->id,
        ]);
    }

    public function testReparentingWithinTheCallersOwnExperimentsStillWorks(): void
    {
        $this->withExceptionHandling();

        $second = $this->treeFor($this->researcher);

        $this->actingAs($this->researcher)
            ->putJson('/api/v1/crawlers/'.$this->mine['crawler']->id, [
                'name' => 'moved',
                'experiment_id' => $second['experiment']->id,
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('crawlers', [
            'id' => $this->mine['crawler']->id,
            'experiment_id' => $second['experiment']->id,
        ]);
    }

    public function testUpdatingAnotherResearchersCrawlerOverHttpIsForbidden(): void
    {
        $this->withExceptionHandling();

        $this->actingAs($this->researcher)
            ->putJson('/api/v1/crawlers/'.$this->theirs['crawler']->id, [
                'name' => 'stolen',
                'experiment_id' => $this->mine['experiment']->id,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('crawlers', [
            'id' => $this->theirs['crawler']->id,
            'experiment_id' => $this->theirs['experiment']->id,
            'name' => $this->theirs['crawler']->name,
        ]);
    }

    public function testDeletingAnotherResearchersCrawlerOverHttpIsForbidden(): void
    {
        $this->withExceptionHandling();

        $this->actingAs($this->researcher)
            ->deleteJson('/api/v1/crawlers/'.$this->theirs['crawler']->id)
            ->assertForbidden();

        $this->assertDatabaseHas('crawlers', ['id' => $this->theirs['crawler']->id]);
    }

    /**
     * @param class-string<Model> $parent
     */
    private function validateParent(string $attribute, int $value, string $parent): ValidatorInstance
    {
        return Validator::make(
            [$attribute => $value],
            [$attribute => [new OwnedParent($parent)]]
        );
    }
}
