<?php

namespace Tests\Feature\Isolation;

use App\Http\Middleware\StripClientOwnership;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * The middleware that stops a caller naming a different owner on write.
 *
 * Under derived ownership a client-asserted user_id is not cosmetic: several
 * requests (UpdateJobRequest among them) accept user_id straight through to a
 * $guarded = [] model. The middleware drops it, and the FormRequests that
 * genuinely need the field put the right value back in prepareForValidation()
 * from Auth::id(), which runs after middleware.
 *
 * It must be a no-op whenever isolation is bypassed, or the runner and the
 * upload pipeline lose a field they legitimately set.
 *
 * @internal
 *
 * @coversDefaultClass \App\Http\Middleware\StripClientOwnership
 */
class StripClientOwnershipTest extends IsolationTestCase
{
    /**
     * @covers ::handle
     */
    public function testStripsClientSuppliedOwnerForAnIsolatedResearcher(): void
    {
        $this->enableIsolation();
        $this->actingAs($this->researcher);

        $request = $this->pass($this->bodyRequest($this->otherResearcher));

        $this->assertFalse($request->request->has('user_id'), 'user_id must be removed from the request body.');
        $this->assertNull($request->input('user_id'));
        $this->assertSame('renamed', $request->input('name'), 'Only user_id may be removed.');
    }

    /**
     * @covers ::handle
     */
    public function testStripsClientSuppliedOwnerFromTheQueryString(): void
    {
        $this->enableIsolation();
        $this->actingAs($this->researcher);

        $request = $this->pass($this->queryRequest($this->otherResearcher));

        $this->assertFalse($request->query->has('user_id'));
        $this->assertSame('renamed', $request->query->get('name'));
    }

    /**
     * @covers ::handle
     */
    public function testLeavesTheOwnerAloneForTheServiceAccount(): void
    {
        $this->enableIsolation();
        $this->actingAs($this->serviceAccount);

        $body = $this->pass($this->bodyRequest($this->otherResearcher));
        $query = $this->pass($this->queryRequest($this->otherResearcher));

        $this->assertSame((string) $this->otherResearcher->id, (string) $body->input('user_id'));
        $this->assertSame((string) $this->otherResearcher->id, (string) $query->query->get('user_id'));
    }

    /**
     * @covers ::handle
     */
    public function testLeavesTheOwnerAloneWhileIsolationIsDisabled(): void
    {
        $this->disableIsolation();
        $this->actingAs($this->researcher);

        $request = $this->pass($this->bodyRequest($this->otherResearcher));

        $this->assertSame((string) $this->otherResearcher->id, (string) $request->input('user_id'));
    }

    /**
     * @covers ::handle
     */
    public function testLeavesTheOwnerAloneWhenNobodyIsAuthenticated(): void
    {
        $this->enableIsolation();

        $request = $this->pass($this->bodyRequest($this->otherResearcher));

        $this->assertSame((string) $this->otherResearcher->id, (string) $request->input('user_id'));
    }

    /**
     * End to end over a real route. UpdateJobRequest lists user_id as an
     * accepted field and Job is $guarded = [], so without the middleware this
     * PUT would re-own the job.
     *
     * @covers ::handle
     */
    public function testResearcherCannotReOwnAJobOverHttp(): void
    {
        $this->enableIsolation();

        $job = $this->mine['job'];

        $this->actingAs($this->researcher)
            ->putJson('/api/v1/jobs/'.$job->id, [
                'name' => 'renamed',
                'user_id' => $this->otherResearcher->id,
            ])
            ->assertSuccessful();

        $job->refresh();

        $this->assertSame('renamed', $job->name, 'The rest of the payload must still be applied.');
        $this->assertSame(
            (int) $this->serviceAccount->id,
            (int) $job->user_id,
            'A client-supplied user_id must not reach the database.'
        );
    }

    /**
     * The bypass half of the same route: with the flag off, behaviour is
     * exactly what it was before this branch.
     *
     * @covers ::handle
     */
    public function testClientSuppliedOwnerStillReachesTheDatabaseWhileDisabled(): void
    {
        $this->disableIsolation();

        $job = $this->mine['job'];

        $this->actingAs($this->researcher)
            ->putJson('/api/v1/jobs/'.$job->id, [
                'name' => 'renamed',
                'user_id' => $this->otherResearcher->id,
            ])
            ->assertSuccessful();

        $this->assertSame((int) $this->otherResearcher->id, (int) $job->refresh()->user_id);
    }

    private function bodyRequest(User $claimedOwner): Request
    {
        return Request::create('/api/v1/jobs/1', 'PUT', [
            'name' => 'renamed',
            'user_id' => $claimedOwner->id,
        ]);
    }

    private function queryRequest(User $claimedOwner): Request
    {
        return Request::create('/api/v1/jobs?name=renamed&user_id='.$claimedOwner->id, 'GET');
    }

    private function pass(Request $request): Request
    {
        (new StripClientOwnership())->handle($request, fn (Request $r): Response => new Response());

        return $request;
    }
}
