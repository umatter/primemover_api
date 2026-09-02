<?php

namespace Tests\Feature\RunnerAssignment;

/**
 * A researcher chooses where an experiment runs; the API only accepts a
 * configured runner account as the target.
 *
 * @internal
 *
 * @coversDefaultClass \App\Modules\Experiment\Http\Request\ExperimentStoreRequest
 */
class ExperimentRequestTest extends RunnerAssignmentTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->enableIsolation();
        $this->withExceptionHandling();
        $this->actingAs($this->researcher);
    }

    /**
     * @covers ::rules
     */
    public function test_researcher_can_assign_a_runner_on_create(): void
    {
        $response = $this->postJson('/api/v1/experiments', [
            'name' => 'VM study',
            'contact' => 'r@example.org',
            'runner_user_id' => $this->secondRunner->id,
        ])->assertCreated();

        $this->assertSame($this->secondRunner->id, (int) $response->json('data.runner_user_id'));
        $this->assertSame($this->researcher->id, (int) $response->json('data.user_id'), 'Ownership still comes from Auth.');
        $this->assertDatabaseHas('experiments', ['name' => 'VM study', 'runner_user_id' => $this->secondRunner->id]);
    }

    /**
     * @covers ::rules
     */
    public function test_omitting_the_runner_stores_null(): void
    {
        $response = $this->postJson('/api/v1/experiments', [
            'name' => 'Default study',
            'contact' => 'r@example.org',
        ])->assertCreated();

        $this->assertNull($response->json('data.runner_user_id'));
    }

    /**
     * @covers ::rules
     */
    public function test_a_non_runner_target_is_rejected(): void
    {
        $this->postJson('/api/v1/experiments', [
            'name' => 'Nope',
            'contact' => 'r@example.org',
            'runner_user_id' => $this->otherResearcher->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('runner_user_id');

        $this->assertDatabaseMissing('experiments', ['name' => 'Nope']);
    }

    /**
     * @covers ::rules
     */
    public function test_runner_can_be_changed_and_cleared_on_update(): void
    {
        $experiment = $this->unassigned['experiment'];

        $this->putJson('/api/v1/experiments/'.$experiment->id, [
            'name' => $experiment->name,
            'runner_user_id' => $this->secondRunner->id,
        ])->assertOk();
        $this->assertSame($this->secondRunner->id, (int) $experiment->fresh()->runner_user_id);

        $this->putJson('/api/v1/experiments/'.$experiment->id, [
            'name' => $experiment->name,
            'runner_user_id' => null,
        ])->assertOk();
        $this->assertNull($experiment->fresh()->runner_user_id);
    }

    /**
     * A JSON boolean casts to an integer under Laravel's 'integer' rule (true
     * -> 1), which would silently target runner 1. Reject it explicitly.
     *
     * @covers ::rules
     */
    public function test_boolean_runner_id_is_rejected(): void
    {
        $this->postJson('/api/v1/experiments', [
            'name' => 'Nope',
            'contact' => 'r@example.org',
            'runner_user_id' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors('runner_user_id');

        $this->assertDatabaseMissing('experiments', ['name' => 'Nope']);
    }

    /**
     * An HTML form sends every field as a string; the numeric string a form
     * would submit must still be accepted and stored as an integer.
     *
     * @covers ::rules
     */
    public function test_string_form_of_a_runner_id_is_accepted(): void
    {
        $response = $this->postJson('/api/v1/experiments', [
            'name' => 'Form study',
            'contact' => 'r@example.org',
            'runner_user_id' => (string) $this->secondRunner->id,
        ])->assertCreated();

        $this->assertSame($this->secondRunner->id, (int) $response->json('data.runner_user_id'));
        $this->assertDatabaseHas('experiments', ['name' => 'Form study', 'runner_user_id' => $this->secondRunner->id]);
    }
}
