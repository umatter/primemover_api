<?php

namespace Tests\Feature\Modules\Param\Http\Controller\Api\v1;

use App\Modules\Param\Http\Controller\Api\v1\ParamController;
use App\Modules\Param\Model\Param;
use App\Modules\Param\Repository\ParamRepository;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Modules\Param\Http\Controller\Api\v1\ParamController
 */
class ParamControllerTest extends TestCase
{
    /**
     * @covers ::index
     */
    public function test_index(): void
    {
        $controller = new ParamController(new ParamRepository(new Param()));
        $result = $controller->index();

        $this->assertInstanceOf(ResourceCollection::class, $result);

        foreach ($result->resource as $param) {
            $this->assertInstanceOf(Param::class, $param);
        }
    }

    /**
     * @covers ::store
     */
    public function test_store(): void
    {
        $data = [
            'pi' => -0.50049837032537,
            'psi' => 0.086705270313224,
            'alpha' => 0.36951623165984,
            'tau' => 1.0,
            'kappa' => 1,
            'beta' => 0.0034437549399552,
            'configuration_id' => 1,
        ];

        $data['search_terms'] = json_encode([
            [
                'term' => 'black american',
                'pi' => -0.424099419061494,
            ],
            [
                'term' => 'proued boys stand',
                'pi' => -0.424099419061495,
            ],
            [
                'term' => 'environmental protection',
                'pi' => -0.424099419061496,
            ],
        ]);

        $data['media_outlet_urls'] = json_encode([
            [
                'domain' => 'cnbc.com',
                'url' => 'https://www.cnbc.com/',
                'pi' => -0.0221973135909118,
            ],
            [
                'domain' => 'theatlantic.com',
                'url' => 'https://www.theatlantic.com/',
                'pi' => -0.417806599458759,
            ],
        ]);

        // Through HTTP: store() validates via ParamStoreRequest now.
        Passport::actingAs($this->user);
        $this->postJson('/api/v1/params', $data)->assertCreated();

        $this->assertDatabaseCount('params', 11);
    }

    /**
     * @covers ::show
     */
    public function test_show(): void
    {
        $data = [
            'user_id' => $this->user->id,
            'pi' => -0.50049837032537,
            'psi' => 0.086705270313224,
            'alpha' => 0.36951623165984,
            'tau' => 1.0,
            'kappa' => 1,
            'beta' => 0.0034437549399552,
            'configuration_id' => 1,
        ];

        $data['search_terms'] = json_encode([
            [
                'term' => 'black american',
                'pi' => -0.424099419061494,
            ],
            [
                'term' => 'proued boys stand',
                'pi' => -0.424099419061495,
            ],
            [
                'term' => 'environmental protection',
                'pi' => -0.424099419061496,
            ],
        ]);

        $data['media_outlet_urls'] = json_encode([
            [
                'domain' => 'cnbc.com',
                'url' => 'https://www.cnbc.com/',
                'pi' => -0.0221973135909118,
            ],
            [
                'domain' => 'theatlantic.com',
                'url' => 'https://www.theatlantic.com/',
                'pi' => -0.417806599458759,
            ],
        ]);

        $param = Param::create($data);

        $controller = new ParamController(new ParamRepository(new Param()));
        $result = $controller->show($param->id);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Param::class, $result->resource);
        $this->assertEquals($param->id, $result->resource->id);
    }

    /**
     * @covers ::edit
     */
    public function test_edit(): void
    {
        $data = [
            'user_id' => $this->user->id,
            'pi' => -0.50049837032537,
            'psi' => 0.086705270313224,
            'alpha' => 0.36951623165984,
            'tau' => 1.0,
            'kappa' => 1,
            'beta' => 0.0034437549399552,
            'configuration_id' => 1,
        ];

        $data['search_terms'] = json_encode([
            [
                'term' => 'black american',
                'pi' => -0.424099419061494,
            ],
            [
                'term' => 'proued boys stand',
                'pi' => -0.424099419061495,
            ],
            [
                'term' => 'environmental protection',
                'pi' => -0.424099419061496,
            ],
        ]);

        $data['media_outlet_urls'] = json_encode([
            [
                'domain' => 'cnbc.com',
                'url' => 'https://www.cnbc.com/',
                'pi' => -0.0221973135909118,
            ],
            [
                'domain' => 'theatlantic.com',
                'url' => 'https://www.theatlantic.com/',
                'pi' => -0.417806599458759,
            ],
        ]);

        $param = Param::create($data);

        $controller = new ParamController(new ParamRepository(new Param()));
        $result = $controller->edit($param);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Param::class, $result->resource);
        $this->assertEquals($param->id, $result->resource->id);
    }

    /**
     * @covers ::update
     */
    public function test_update(): void
    {
        $data = [
            'user_id' => $this->user->id,
            'pi' => -0.50049837032537,
            'psi' => 0.086705270313224,
            'alpha' => 0.36951623165984,
            'tau' => 1.0,
            'kappa' => 1,
            'beta' => 0.0034437549399552,
            'configuration_id' => 1,
            'search_terms' => json_encode([
                [
                    'term' => 'black american update',
                    'pi' => -0.424099419061494,
                ],
                [
                    'term' => 'proued boys stand',
                    'pi' => -0.424099419061495,
                ],
                [
                    'term' => 'environmental protection',
                    'pi' => -0.424099419061496,
                ],
            ]),
            'media_outlet_urls' => json_encode([
                [
                    'domain' => 'cnbc.com',
                    'url' => 'https://www.cnbc.com/',
                    'pi' => -0.0221973135909118,
                ],
                [
                    'domain' => 'theatlantic.com',
                    'url' => 'https://www.theatlantic.com/',
                    'pi' => -0.417806599458759,
                ],
            ]),
        ];

        $param = Param::create($data);

        // Through HTTP: update() validates via ParamUpdateRequest now.
        Passport::actingAs($this->user);
        $this->putJson('/api/v1/params/'.$param->id, $data)->assertOk();

        $data['search_terms'] = json_encode($data['search_terms']);
        $this->assertDatabaseHas('params', $data);
    }

    /**
     * @covers ::destroy
     */
    public function destroy(): void
    {
        $data = [
            'user_id' => $this->user->id,
            'pi' => -0.50049837032537,
            'psi' => 0.086705270313224,
            'alpha' => 0.36951623165984,
            'tau' => 1.0,
            'kappa' => 1,
            'beta' => 0.0034437549399552,
            'configuration_id' => 1,
            'search_terms' => json_encode([
                [
                    'term' => 'black american update',
                    'pi' => -0.424099419061494,
                ],
                [
                    'term' => 'proued boys stand',
                    'pi' => -0.424099419061495,
                ],
                [
                    'term' => 'environmental protection',
                    'pi' => -0.424099419061496,
                ],
            ]),
            'media_outlet_urls' => json_encode([
                [
                    'domain' => 'cnbc.com',
                    'url' => 'https://www.cnbc.com/',
                    'pi' => -0.0221973135909118,
                ],
                [
                    'domain' => 'theatlantic.com',
                    'url' => 'https://www.theatlantic.com/',
                    'pi' => -0.417806599458759,
                ],
            ]),
        ];

        $param = Param::create($data);

        $controller = new ParamController(new ParamRepository(new Param()));
        $result = $controller->destroy($param);

        $this->assertDatabaseMissing('params', $param->toArray());
        $this->assertInstanceOf(ResourceCollection::class, $result);

        foreach ($result->resource as $param) {
            $this->assertInstanceOf(Param::class, $param);
        }
    }
}
