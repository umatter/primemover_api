<?php

namespace Tests\Feature\Modules\Param\Repository;

use App\Models\User;
use App\Modules\Configuration\Model\Configuration;
use App\Modules\Param\Model\Param;
use App\Modules\Param\Repository\ParamRepository;
use Illuminate\Database\Eloquent\Collection;
use Mockery as m;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \App\Modules\Param\Repository\ParamRepository
 */
class ParamRepositoryTest extends TestCase
{
    /**
     * @covers ::all
     */
    public function test_get_data(): void
    {
        $service = new ParamRepository(new Param());
        $result = $service->all();

        $this->assertInstanceOf(Collection::class, $result);

        foreach ($result as $param) {
            $this->assertInstanceOf(Param::class, $param);
        }
    }

    /**
     * @covers ::getById
     */
    public function test_get_by_id(): void
    {
        $param = m::mock(Param::class);
        $param->allows('getAttribute')->with('id')->andReturns(1);
        $param->allows('findOrFail')->andReturns(new Param([
            'pi' => -0.50049837032537,
            'psi' => 0.086705270313224,
            'alpha' => 0.36951623165984,
            'tau' => 1.0,
            'kappa' => 1,
            'beta' => 0.0034437549399552,
            'search_terms' => [
                [
                    'term' => 'black american',
                    'pi' => -0.424099419061494,
                ],
                [
                    'term' => 'proud boys stand',
                    'pi' => -0.483203551983422,
                ],
            ],
            'media_outlet_urls' => [
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
            ],
        ]));

        $service = new ParamRepository(new Param());
        $result = $service->getById($param->id);

        $this->assertInstanceOf(Param::class, $result);
        $this->assertEquals($param->id, $result->id);
    }

    /**
     * @covers ::save
     */
    public function test_save(): void
    {
        $data = [
            'configuration_id' => $this->configuration->id,
            'pi' => -0.50049837032537,
            'psi' => 0.086705270313224,
            'alpha' => 0.36951623165984,
            'tau' => 1.0,
            'kappa' => 1,
            'beta' => 0.0034437549399552,
            'search_terms' => json_encode([
                [
                    'term' => 'black american',
                    'pi' => -0.424099419061494,
                ],
                [
                    'term' => 'proud boys stand',
                    'pi' => -0.483203551983422,
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

        $this->actingAs($this->user);

        $service = new ParamRepository(new Param());
        $result = $service->save($data);

        $data['search_terms'] = json_encode($data['search_terms']);

        $this->assertDatabaseHas('params', $data);
        $this->assertInstanceOf(Param::class, $result);
    }

    /**
     * @covers ::update
     */
    public function test_update(): void
    {
        $data = [
            'tau' => 2.0,
        ];

        $paramMock = m::mock(Param::class);
        $paramMock->allows('getAttribute')->with(['id', 'tau'])->andReturns(['id' => 1, 'tau' => 2.0]);
        $paramMock->allows('firstOrFail')->andReturns(new Param([
            'pi' => -0.50049837032537,
            'psi' => 0.086705270313224,
            'alpha' => 0.36951623165984,
            'tau' => 1.0,
            'kappa' => 1,
            'beta' => 0.0034437549399552,
            'search_terms' => [
                [
                    'term' => 'black american',
                    'pi' => -0.424099419061494,
                ],
                [
                    'term' => 'proud boys stand',
                    'pi' => -0.483203551983422,
                ],
            ],
            'media_outlet_urls' => [
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
            ],
        ]));

        $param = $paramMock->firstOrFail();

        $paramMock->allows('update')->andReturnUsing(function () use ($param, $data) {
            $param->tau = $data['tau'];

            return true;
        });

        $service = new ParamRepository(new Param());
        $result = $service->update($data, $paramMock);

        $this->assertEquals($data['tau'], $param->tau);
        $this->assertInstanceOf(Param::class, $result);
    }

    /**
     * @covers ::delete
     */
    public function test_delete(): void
    {
        $configuration = Configuration::factory()->create();

        $param = Param::create([
            'user_id' => $this->user->id,
            'configuration_id' => $configuration->id,
            'pi' => -0.50049837032537,
            'psi' => 0.086705270313224,
            'alpha' => 0.36951623165984,
            'tau' => 1.0,
            'kappa' => 1,
            'beta' => 0.0034437549399552,
            'search_terms' => json_encode([
                [
                    'term' => 'black american',
                    'pi' => -0.424099419061494,
                ],
                [
                    'term' => 'proud boys stand',
                    'pi' => -0.483203551983422,
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
        ]);

        $service = new ParamRepository(new Param());
        $result = $service->delete($param);

        $this->assertDatabaseMissing('params', $param->toArray());
        $this->assertInstanceOf(Collection::class, $result);

        foreach ($result as $param) {
            $this->assertInstanceOf(Param::class, $param);
            $this->assertInstanceOf(User::class, $param->user);
            $this->assertInstanceOf(Configuration::class, $param->configuration);
        }
    }
}
