<?php

namespace Tests\Feature\Isolation;

use App\Modules\Crawler\Model\Crawler;
use App\Modules\Proxy\Model\Proxy;
use Laravel\Passport\Passport;

/**
 * Who reads a proxy's password, and what an edit without one does.
 *
 * Every proxy's credentials went to every login: GET proxies, GET proxies/{id}
 * and every crawler read that loads its proxy (umatter/primemover_api#96). Only
 * the runners need them, and they log in as service accounts.
 *
 * @internal
 *
 * @coversDefaultClass \App\Modules\Proxy\Model\Proxy
 */
class ProxyPasswordTest extends IsolationTestCase
{
    private Proxy $ownProxy;

    private Proxy $theirProxy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->enableIsolation();

        $this->ownProxy = Proxy::factory()->create(['user_id' => $this->researcher->id, 'password' => 'own-secret', 'active' => 1]);
        $this->theirProxy = Proxy::factory()->create(['user_id' => $this->otherResearcher->id, 'password' => 'their-secret', 'active' => 1]);
    }

    /**
     * @covers ::toArray
     */
    public function test_a_researcher_never_reads_a_password(): void
    {
        Passport::actingAs($this->researcher);

        foreach ([$this->ownProxy, $this->theirProxy] as $proxy) {
            $this->getJson("/api/v1/proxies/{$proxy->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $proxy->id)
                ->assertJsonMissingPath('data.password');
        }

        $list = $this->getJson('/api/v1/proxies')->assertOk();
        $this->assertNotEmpty($list->json('data'));
        foreach ($list->json('data') as $row) {
            $this->assertArrayNotHasKey('password', $row);
        }
    }

    /**
     * The nested proxy on a crawler read is serialised by the same model.
     *
     * @covers ::toArray
     */
    public function test_a_researcher_reads_no_password_through_a_crawler(): void
    {
        $crawler = Crawler::factory()->create([
            'user_id' => $this->serviceAccount->id,
            'experiment_id' => $this->mine['experiment']->id,
            'proxy_id' => $this->ownProxy->id,
        ]);
        Passport::actingAs($this->researcher);

        $response = $this->getJson("/api/v1/crawlers/{$crawler->id}")->assertOk();

        $this->assertSame($this->ownProxy->id, $response->json('data.proxy.id'));
        $this->assertArrayNotHasKey('password', $response->json('data.proxy'));
    }

    /**
     * @covers ::toArray
     */
    public function test_a_service_account_still_reads_it(): void
    {
        Passport::actingAs($this->serviceAccount);

        $this->getJson("/api/v1/proxies/{$this->theirProxy->id}")
            ->assertOk()
            ->assertJsonPath('data.password', 'their-secret');
    }

    /**
     * @covers ::toArray
     */
    public function test_nothing_changes_while_isolation_is_off(): void
    {
        $this->disableIsolation();
        Passport::actingAs($this->researcher);

        $this->getJson("/api/v1/proxies/{$this->theirProxy->id}")
            ->assertOk()
            ->assertJsonPath('data.password', 'their-secret');
    }

    /**
     * An edit form never has the password, so it sends it empty.
     *
     * @covers \App\Modules\Proxy\Http\Request\ProxyUpdateRequest
     */
    public function test_an_empty_password_keeps_the_stored_one(): void
    {
        Passport::actingAs($this->researcher);

        foreach ([null, '', '   '] as $empty) {
            $this->putJson("/api/v1/proxies/{$this->ownProxy->id}", ['name' => 'renamed', 'password' => $empty])
                ->assertOk();

            $fresh = $this->ownProxy->fresh();
            $this->assertSame('renamed', $fresh->name);
            $this->assertSame('own-secret', $fresh->password, var_export($empty, true));
        }
    }

    /**
     * @covers \App\Modules\Proxy\Http\Request\ProxyUpdateRequest
     */
    public function test_a_new_password_is_written(): void
    {
        Passport::actingAs($this->researcher);

        $this->putJson("/api/v1/proxies/{$this->ownProxy->id}", ['password' => 'rotated'])
            ->assertOk()
            ->assertJsonMissingPath('data.password');

        $this->assertSame('rotated', $this->ownProxy->fresh()->password);
    }
}
