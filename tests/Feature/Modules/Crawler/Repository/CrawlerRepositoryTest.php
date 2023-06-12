<?php

namespace Tests\Feature\Modules\Crawler\Repository;

use App\Modules\Agent\Model\Agent;
use App\Modules\Configuration\Model\Configuration;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Crawler\Repository\CrawlerRepository;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Proxy\Model\Proxy;
use App\Modules\Queue\Model\Queue;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Mockery as m;
use Spatie\QueryBuilder\QueryBuilder;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Modules\Crawler\Repository\CrawlerRepository
 */
class CrawlerRepositoryTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @covers ::getData
     */
    public function testGetData(): void
    {
        $experiment = Experiment::factory()->create();

        $service = new CrawlerRepository(new Crawler());
        $result = $service->getData($experiment);

        $this->assertInstanceOf(QueryBuilder::class, $result);

        foreach ($result as $crawler) {
            $this->assertInstanceOf(Crawler::class, $crawler);
        }
    }

    /**
     * @covers ::getById
     */
    public function testGetById(): void
    {
        $crawlerMock = m::mock(Crawler::class);
        $crawlerMock->expects('firstOrFail')->andReturns(new Crawler([
            'id' => 1,
            'user_id' => 1,
            'name' => 'crawler name',
            'description' => 'crawler description',
            'active' => 1,
            'proxy_id' => 1,
            'agent_id' => 1,
            'configuration_id' => 1,
            'experiment_id' => 1,
        ]));

        $crawler = $crawlerMock->firstOrFail();

        $service = new CrawlerRepository(new Crawler());
        $result = $service->getById($crawler);

        $this->assertInstanceOf(Crawler::class, $result);
        $this->assertEquals($crawler->id, $result->id);
    }

    /**
     * @covers ::inactive
     */
    public function testInactive(): void
    {
        $service = new CrawlerRepository(new Crawler());
        $result = $service->inactive();

        $this->assertInstanceOf(Collection::class, $result);

        foreach ($result as $crawler) {
            $this->assertInstanceOf(Crawler::class, $crawler);
        }
    }

    /**
     * @covers ::processed
     */
    public function testProcessed(): void
    {
        $service = new CrawlerRepository(new Crawler());
        $result = $service->processed();

        $this->assertInstanceOf(Collection::class, $result);

        foreach ($result as $crawler) {
            $this->assertInstanceOf(Crawler::class, $crawler);
            $this->assertEquals(1, $crawler->active);
            $this->assertEquals(0, $crawler->testing);
            foreach ($crawler->queues as $queue) {
                $this->assertEquals(1, $queue->processed);
            }
        }
    }

    /**
     * @covers ::unprocessed
     */
    public function testUnprocessed(): void
    {
        $service = new CrawlerRepository(new Crawler());
        $result = $service->unprocessed();

        $this->assertInstanceOf(Collection::class, $result);

        foreach ($result as $crawler) {
            $this->assertInstanceOf(Crawler::class, $crawler);
            $this->assertEquals(0, $crawler->testing);
            $this->assertEquals(1, $crawler->active);

            foreach ($crawler->queues as $queue) {
                $this->assertInstanceOf(Queue::class, $queue);
                $this->assertEquals(0, $queue->processed);
            }
        }
    }

    /**
     * @covers ::reviewed
     */
    public function testReviewed(): void
    {
        $service = new CrawlerRepository(new Crawler());
        $result = $service->reviewed();

        $this->assertInstanceOf(Collection::class, $result);

        foreach ($result as $crawler) {
            $this->assertInstanceOf(Crawler::class, $crawler);
            $this->assertEquals(1, $crawler->active);
            $this->assertEquals(1, $crawler->testing);

            foreach ($crawler->queues as $queue) {
                $this->assertInstanceOf(Queue::class, $queue);
                $this->assertEquals(1, $queue->reviewed);
            }
        }
    }

    /**
     * @covers ::testing
     */
    public function testTesting(): void
    {
        $service = new CrawlerRepository(new Crawler());
        $result = $service->testing();

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);

        foreach ($result as $crawler) {
            $this->assertInstanceOf(Crawler::class, $crawler);
            $this->assertEquals(1, $crawler->testing);
            $this->assertEquals(1, $crawler->active);
        }
    }

    /**
     * @covers ::save
     */
    public function testSave(): void
    {
        $experiment = Experiment::factory()->create();

        $data = [
            'name' => 'Name',
            'description' => 'Beschreibung',
            'experiment_id' => $experiment->id,
            'active' => 0,
            'user_id' => $this->user->id,
        ];

        $service = new CrawlerRepository(new Crawler());
        $result = $service->save($data);

        $this->assertDatabaseHas('crawlers', $data);
        $this->assertInstanceOf(Crawler::class, $result);
    }

    /**
     * @covers ::update
     */
    public function testUpdate(): void
    {
        $crawlerMock = m::mock(Crawler::class);
        $crawlerMock->expects('firstOrFail')->andReturns(new Crawler([
            'id' => 1,
            'user_id' => 1,
            'name' => 'crawler name',
            'description' => 'crawler description',
            'active' => 1,
            'proxy_id' => 1,
            'agent_id' => 1,
            'configuration_id' => 1,
            'experiment_id' => 1,
        ]));

        $data = [
            'name' => 'update name crawler',
            'active' => 1,
        ];

        $crawlerMock->allows('getAttribute')->with('id')->andReturns(1);
        $crawlerMock->allows('getAttribute')->with('name')->andReturns($data['name']);
        $crawlerMock->allows('getAttribute')->with('active')->andReturns($data['active']);

        $crawler = $crawlerMock->firstOrFail();

        $crawlerMock->expects('update')->andReturnUsing(function () use ($crawler, $data) {
            $crawler->name = $data['name'];
            $crawler->active = $data['active'];

            return true;
        });

        $crawlerMock->allows('fresh')->andReturnUsing(fn () => $crawler);

        $service = new CrawlerRepository(new Crawler());
        $result = $service->update($data, $crawlerMock);

        $this->assertInstanceOf(Crawler::class, $result);
        $this->assertEquals($data['name'], $result->name);
        $this->assertEquals($data['active'], $result->active);
    }

    /**
     * @covers ::disableProdCrawlers
     */
    public function testDisableProdCrawlers(): void
    {
        $service = new CrawlerRepository(new Crawler());
        $result = $service->disableProdCrawlers();

        $this->assertInstanceOf(Collection::class, $result);

        foreach ($result as $crawler) {
            $this->assertInstanceOf(Crawler::class, $crawler);
            $this->assertEquals(0, $crawler->active);
        }
    }

    /**
     * @covers ::forceDelete
     */
    public function testForceDelete(): void
    {
        $proxy = Proxy::factory()->create();
        $agent = Agent::factory()->create();
        $configuration = Configuration::factory()->create();
        $experiment = Experiment::factory()->create();

        $crawler = Crawler::factory()->create([
            'proxy_id' => $proxy->id,
            'agent_id' => $agent->id,
            'configuration_id' => $configuration->id,
            'experiment_id' => $experiment->id,
        ]);

        $service = new CrawlerRepository(new Crawler());
        $result = $service->forceDelete($crawler);

        $crawler->setAppends([]);
        $this->assertDatabaseMissing('crawlers', $crawler->toArray());

        $this->assertInstanceOf(Collection::class, $result);

        foreach ($result as $crawler) {
            $this->assertInstanceOf(Crawler::class, $crawler);
        }
    }

    /**
     * @covers ::delete
     */
    public function testDelete(): void
    {
        $proxy = Proxy::factory()->create();
        $agent = Agent::factory()->create();
        $configuration = Configuration::factory()->create();
        $experiment = Experiment::factory()->create();

        $crawler = Crawler::factory()->create([
            'proxy_id' => $proxy->id,
            'agent_id' => $agent->id,
            'configuration_id' => $configuration->id,
            'experiment_id' => $experiment->id,
        ]);

        $service = new CrawlerRepository(new Crawler());
        $result = $service->delete($crawler);

        $crawler->setAppends([]);
        $this->assertDatabaseMissing('crawlers', $crawler->toArray());
        $this->assertInstanceOf(Collection::class, $result);
    }

    /**
     * @covers ::ip
     */
    public function testIp(): void
    {
        self::markTestSkipped('There is an error in the repository');
        $ip = '127.0.0.2';

        $service = new CrawlerRepository(new Crawler());
        $result = $service->ip($ip);

        $this->assertInstanceOf(JsonResponse::class, $result);
    }

    /**
     * @covers ::isLocalAddress
     */
    public function testIsLocalAddress(): void
    {
        $ip = '127.0.0.1';

        $service = new CrawlerRepository(new Crawler());
        $result = $service->isLocalAddress($ip);

        $this->assertIsBool($result);
    }

    /**
     * @covers ::undo
     */
    public function testUndo(): void
    {
        $service = new CrawlerRepository(new Crawler());
        $result = $service->undo();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertDatabaseMissing('crawlers', ['updated' => 1]);

        foreach ($result as $crawler) {
            $this->assertInstanceOf(Crawler::class, $crawler);
        }
    }

    /**
     * @covers ::cleanup
     */
    public function testCleanUp(): void
    {
        $service = new CrawlerRepository(new Crawler());
        $result = $service->cleanup();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertDatabaseMissing('crawlers', ['testing' => 0]);

        foreach ($result as $crawler) {
            $this->assertInstanceOf(Crawler::class, $crawler);
        }
    }
}
