<?php

namespace Tests\Feature\RunnerAssignment;

/**
 * GET /runners: the accounts an experiment may be assigned to, so a client can
 * show names instead of ids and preselect the default.
 *
 * @internal
 *
 * @coversDefaultClass \App\Http\Controllers\API\v1\RunnerController
 */
class RunnersEndpointTest extends RunnerAssignmentTestCase
{
    /**
     * @covers ::index
     */
    public function test_lists_the_configured_runner_accounts_with_the_default_flagged(): void
    {
        $this->enableIsolation();

        $response = $this->actingAs($this->researcher)
            ->getJson('/api/v1/runners')
            ->assertOk();

        $this->assertSame([
            [
                'id' => $this->defaultRunner->id,
                'name' => $this->defaultRunner->name,
                'email' => $this->defaultRunner->email,
                'is_default' => true,
            ],
            [
                'id' => $this->secondRunner->id,
                'name' => $this->secondRunner->name,
                'email' => $this->secondRunner->email,
                'is_default' => false,
            ],
        ], $response->json());
    }

    /**
     * A researcher who is not a runner still sees the list: choosing a runner is
     * exactly what non-runners need it for.
     *
     * @covers ::index
     */
    public function test_a_plain_researcher_is_not_listed_and_may_still_read_it(): void
    {
        $this->enableIsolation();

        $ids = array_column(
            $this->actingAs($this->researcher)->getJson('/api/v1/runners')->json(),
            'id'
        );

        $this->assertNotContains($this->researcher->id, $ids);
        $this->assertCount(2, $ids);
    }

    /**
     * @covers ::index
     */
    public function test_requires_authentication(): void
    {
        $this->withExceptionHandling();

        $this->getJson('/api/v1/runners')->assertUnauthorized();
    }
}
