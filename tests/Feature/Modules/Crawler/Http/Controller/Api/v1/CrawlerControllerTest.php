<?php

namespace Tests\Feature\Modules\Crawler\Http\Controller\Api\v1;

use App\Modules\Agent\Model\Agent;
use App\Modules\Configuration\Model\Configuration;
use App\Modules\Crawler\Http\Controller\Api\v1\CrawlerController;
use App\Modules\Crawler\Http\Request\StoreCrawlerRequest;
use App\Modules\Crawler\Http\Request\UpdateCrawlerRequest;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Crawler\Repository\CrawlerRepository;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Proxy\Model\Proxy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Mockery as m;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Modules\Crawler\Http\Controller\Api\v1\CrawlerController
 */
class CrawlerControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->user);
        config(['primemover.runner.default_user_id' => $this->user->id]);
    }

    /**
     * @covers ::index
     */
    public function test_index(): void
    {
        $request = new Request();

        $controller = new CrawlerController(new CrawlerRepository(new Crawler()));
        $result = $controller->index($request);

        $this->assertInstanceOf(ResourceCollection::class, $result);
        $this->assertInstanceOf(Crawler::class, $result->resource[0]);
    }

    /**
     * @covers ::inactive
     */
    public function test_inactive(): void
    {
        $controller = new CrawlerController(new CrawlerRepository(new Crawler()));
        $result = $controller->inactive();

        $this->assertInstanceOf(ResourceCollection::class, $result);
        $this->assertInstanceOf(Collection::class, $result->resource);
        if ( ! empty($result->resource[0])) {
            $this->assertInstanceOf(Crawler::class, $result->resource[0]);
        }
    }

    /**
     * @covers ::withProcessedQueues
     */
    public function test_with_processed_queues(): void
    {
        $controller = new CrawlerController(new CrawlerRepository(new Crawler()));
        $result = $controller->withProcessedQueues();

        $this->assertInstanceOf(ResourceCollection::class, $result);
        $this->assertInstanceOf(LengthAwarePaginator::class, $result->resource);

        foreach ($result->resource as $crawler) {
            $this->assertInstanceOf(Crawler::class, $crawler);
        }
    }

    /**
     * @covers ::withReviewedQueues
     */
    public function test_with_reviewed_queues(): void
    {
        $controller = new CrawlerController(new CrawlerRepository(new Crawler()));
        $result = $controller->withReviewedQueues();

        $this->assertInstanceOf(ResourceCollection::class, $result);
        $this->assertInstanceOf(LengthAwarePaginator::class, $result->resource);

        foreach ($result->resource as $crawler) {
            $this->assertInstanceOf(Crawler::class, $crawler);
        }
    }

    /**
     * @covers ::withUnprocessedQueues
     */
    public function test_with_unprocessed_queues(): void
    {
        $controller = new CrawlerController(new CrawlerRepository(new Crawler()));
        $result = $controller->withUnprocessedQueues();

        $this->assertInstanceOf(ResourceCollection::class, $result);
        $this->assertInstanceOf(Collection::class, $result->resource);

        foreach ($result->resource as $crawler) {
            $this->assertInstanceOf(Crawler::class, $crawler);
        }
    }

    /**
     * @covers ::testing
     */
    public function test_testing(): void
    {
        $controller = new CrawlerController(new CrawlerRepository(new Crawler()));
        $result = $controller->testing();

        $this->assertInstanceOf(ResourceCollection::class, $result);

        foreach ($result->resource as $crawler) {
            $this->assertInstanceOf(Crawler::class, $crawler);
        }
    }

    /**
     * @covers ::create
     */
    public function test_create(): void
    {
        $controller = new CrawlerController(new CrawlerRepository(new Crawler()));
        $result = $controller->create();

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Crawler::class, $result->resource);
    }

    /**
     * @covers ::store
     */
    public function test_store(): void
    {
        $experiment = Experiment::query()->create([
            'user_id' => $this->user->id,
            'name' => 'name experiment',
            'description' => 'description experiment',
        ]);

        $data = [
            'name' => 'Name',
            'description' => 'Beschreibung',
            'experiment_id' => $experiment->id,
            'active' => 0,
            'user_id' => $this->user->id,
        ];

        $requestMock = m::mock(StoreCrawlerRequest::class)->makePartial();
        $requestMock->allows('validated')->andReturns($data);

        $controller = new CrawlerController(new CrawlerRepository(new Crawler()));
        $result = $controller->store($requestMock);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Crawler::class, $result->resource);
        $this->assertDatabaseHas('crawlers', $data);
    }

    /**
     * @covers ::show
     */
    public function test_show(): void
    {
        $experiment = Experiment::query()->create([
            'user_id' => $this->user->id,
            'name' => 'name experiment',
            'description' => 'description experiment',
        ]);

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
            'experiment_id' => $experiment->id,
        ]));

        $crawler = $crawlerMock->firstOrFail();

        $controller = new CrawlerController(new CrawlerRepository(new Crawler()));
        $result = $controller->show($crawler);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Crawler::class, $result->resource);
        $this->assertEquals($crawler->id, $result->resource->id);
    }

    /**
     * @covers ::edit
     */
    public function test_edit(): void
    {
        $experiment = Experiment::query()->create([
            'user_id' => $this->user->id,
            'name' => 'name experiment',
            'description' => 'description experiment',
        ]);

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
            'experiment_id' => $experiment->id,
        ]));

        $crawler = $crawlerMock->firstOrFail();

        $controller = new CrawlerController(new CrawlerRepository(new Crawler()));
        $result = $controller->edit($crawler);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Crawler::class, $result->resource);
        $this->assertEquals($crawler->id, $result->resource->id);
    }

    /**
     * @covers ::update
     */
    public function test_update(): void
    {
        $experiment = Experiment::query()->create([
            'user_id' => $this->user->id,
            'name' => 'name experiment',
            'description' => 'description experiment',
        ]);

        $crawlerMock = m::mock(Crawler::class);

        $crawlerMock->expects('firstOrFail')->andReturns(new Crawler([
            'user_id' => 1,
            'name' => 'crawler name',
            'description' => 'crawler description',
            'active' => 1,
            'proxy_id' => 1,
            'agent_id' => 1,
            'configuration_id' => 1,
            'experiment_id' => $experiment->id,
        ]));

        $crawler = $crawlerMock->firstOrFail();

        $data = [
            'name' => 'Name',
            'description' => 'Beschreibung',
            'experiment_id' => $experiment->id,
        ];

        $crawlerMock->allows('update')->andReturnUsing(function () use ($crawler, $data) {
            $crawler['name'] = $data['name'];
            $crawler['description'] = $data['description'];
            $crawler['experiment_id'] = $data['experiment_id'];

            return true;
        });

        $crawlerMock->allows('fresh')->andReturnUsing(fn () => $crawler);

        $requestMock = m::mock(UpdateCrawlerRequest::class)->makePartial();
        $requestMock->allows('validated')->andReturns($data);

        $controller = new CrawlerController(new CrawlerRepository(new Crawler()));
        $result = $controller->update($requestMock, $crawlerMock);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Crawler::class, $result->resource);
    }

    /**
     * @covers ::disableProdCrawlers
     */
    public function test_disable_prod_crawlers(): void
    {
        $controller = new CrawlerController(new CrawlerRepository(new Crawler()));
        $result = $controller->disableProdCrawlers();

        $this->assertInstanceOf(ResourceCollection::class, $result);
        $this->assertInstanceOf(Collection::class, $result->resource);
        $this->assertInstanceOf(Crawler::class, $result->resource[0]);
        $this->assertEquals(0, $result->resource[0]->active);
        $this->assertEquals(0, $result->resource[0]->testing);
    }

    /**
     * @covers ::forceDelete
     */
    public function test_force_delete(): void
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

        $controller = new CrawlerController(new CrawlerRepository(new Crawler()));
        $result = $controller->forceDelete($crawler);

        $this->assertInstanceOf(ResourceCollection::class, $result);
        $this->assertInstanceOf(Collection::class, $result->resource);
        $this->assertInstanceOf(Crawler::class, $result->resource[0]);
        $this->assertDatabaseMissing('crawlers', ['id' => $crawler->id]);
    }

    /**
     * @covers ::destroy
     */
    public function test_destroy(): void
    {
        $experiment = Experiment::create([
            'user_id' => $this->user->id,
            'name' => 'name experiment',
            'description' => 'description experiment',
        ]);

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
            'experiment_id' => $experiment->id,
        ]));

        $crawler = $crawlerMock->firstOrFail();

        $controller = new CrawlerController(new CrawlerRepository(new Crawler()));
        $result = $controller->destroy($crawler);

        $this->assertInstanceOf(ResourceCollection::class, $result);
        $this->assertInstanceOf(Collection::class, $result->resource);
        $this->assertInstanceOf(Crawler::class, $result->resource[0]);
    }

    /**
     * @covers ::ip
     */
    public function test_ip(): void
    {
        self::markTestSkipped('There is an error in the repository');

        $request = new Request([
            'ip' => '127.0.0.3',
        ]);

        $controller = new CrawlerController(new CrawlerRepository(new Crawler()));
        $result = $controller->ip($request->ip);

        $this->assertInstanceOf(JsonResponse::class, $result);
    }

    /**
     * @covers ::undo
     */
    public function test_undo(): void
    {
        $controller = new CrawlerController(new CrawlerRepository(new Crawler()));
        $result = $controller->undo();

        $this->assertInstanceOf(ResourceCollection::class, $result);
        $this->assertInstanceOf(Collection::class, $result->resource);

        $this->assertDatabaseMissing('crawlers', ['updated' => 1]);

        foreach ($result->resource as $crawler) {
            $this->assertInstanceOf(Crawler::class, $crawler);
        }
    }

    /**
     * @covers ::cleanup
     */
    public function test_cleanup(): void
    {
        $controller = new CrawlerController(new CrawlerRepository(new Crawler()));
        $result = $controller->cleanup();

        $this->assertInstanceOf(ResourceCollection::class, $result);
        $this->assertInstanceOf(Collection::class, $result->resource);

        $this->assertDatabaseMissing('crawlers', ['testing' => 0]);

        foreach ($result->resource as $crawler) {
            $this->assertInstanceOf(Crawler::class, $crawler);
        }
    }
}
