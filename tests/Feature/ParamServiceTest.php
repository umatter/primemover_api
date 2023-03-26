<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Configuration\Model\Configuration;
use App\Modules\Param\Http\Service\ParamService;
use App\Modules\Param\Model\Param;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Mockery as m;

class ParamServiceTest extends TestCase
{
    protected $user;

    public function setUp():void 
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /** @test */
    public function testGetData()
    {
        $service = new ParamService(new Param());
        $result = $service->getData();

        $this->assertInstanceOf(Collection::class, $result);
        
        foreach ($result as $param) {
            $this->assertInstanceOf(Param::class, $param);
        }
    }

    /** @test */
    public function testGetById()
    {
        $param = m::mock(Param::class);
        $param->shouldReceive("getAttribute")->with("id")->andReturn(1);
        $param->shouldReceive("findOrFail")->andReturn(new Param([
            "pi" => -0.50049837032537,
            "psi" => 0.086705270313224,
            "alpha" => 0.36951623165984,
            "tau" => 1.0,
            "kappa" => 1,
            "beta" => 0.0034437549399552,
            "search_terms" => [
                [
                    "term" => "black american",
                    "pi" => -0.424099419061494
                ],
                [
                    "term" => "proud boys stand",
                    "pi" => -0.483203551983422
                ]
            ],
            "media_outlet_urls" => [
                [
                    "domain" => "cnbc.com",
                    "url" => "https://www.cnbc.com/",
                    "pi" => -0.0221973135909118
                ],
                [
                    "domain" => "theatlantic.com",
                    "url" => "https://www.theatlantic.com/",
                    "pi" => -0.417806599458759
                ]
            ]
        ]));

        $service = new ParamService(new Param());
        $result = $service->getById($param->id);
 
        $this->assertInstanceOf(Param::class, $result);
        $this->assertEquals($param->id, $result->id);
    }

    /** @test */
    public function testSave()
    {
        $configuration = Configuration::factory()->create();

        $data = [
            "configuration_id" => $configuration->id,
            "pi" => -0.50049837032537,
            "psi" => 0.086705270313224,
            "alpha" => 0.36951623165984,
            "tau" => 1.0,
            "kappa" => 1,
            "beta" => 0.0034437549399552,
            "search_terms" => json_encode([
                [
                    "term" => "black american",
                    "pi" => -0.424099419061494
                ],
                [
                    "term" => "proud boys stand",
                    "pi" => -0.483203551983422
                ]
            ]),
            "media_outlet_urls" => json_encode([
                [
                    "domain" => "cnbc.com",
                    "url" => "https://www.cnbc.com/",
                    "pi" => -0.0221973135909118
                ],
                [
                    "domain" => "theatlantic.com",
                    "url" => "https://www.theatlantic.com/",
                    "pi" => -0.417806599458759
                ]
            ])
        ];

        $service = new ParamService(new Param());
        $result = $service->save($data);

        $data["search_terms"] = json_encode($data["search_terms"]);

        $this->assertDatabaseHas("params", $data);
        $this->assertInstanceOf(Param::class, $result);
    }

    /** @test */
    public function testUpdate()
    {
        $data = [
            "tau" => 2.0
        ];

        $paramMock = m::mock(Param::class);
        $paramMock->shouldReceive("getAttribute")->with(["id", "tau"])->andReturn(["id" => 1, "tau" => 2.0]);
        $paramMock->shouldReceive("firstOrFail")->andReturn(new Param([
            "pi" => -0.50049837032537,
            "psi" => 0.086705270313224,
            "alpha" => 0.36951623165984,
            "tau" => 1.0,
            "kappa" => 1,
            "beta" => 0.0034437549399552,
            "search_terms" => [
                [
                    "term" => "black american",
                    "pi" => -0.424099419061494
                ],
                [
                    "term" => "proud boys stand",
                    "pi" => -0.483203551983422
                ]
            ],
            "media_outlet_urls" => [
                [
                    "domain" => "cnbc.com",
                    "url" => "https://www.cnbc.com/",
                    "pi" => -0.0221973135909118
                ],
                [
                    "domain" => "theatlantic.com",
                    "url" => "https://www.theatlantic.com/",
                    "pi" => -0.417806599458759
                ]
            ]
        ]));

        $param = $paramMock->firstOrFail();

        $paramMock->shouldReceive("update")->andReturnUsing(function () use ($param, $data) {
            $param->tau = $data['tau'];
            return true;
        });

        $service = new ParamService(new Param());
        $result = $service->update($data, $paramMock);

        $this->assertEquals($data["tau"], $param->tau);
        $this->assertInstanceOf(Param::class, $result);
    }

    /** @test */
    public function testDelete()
    {
        $configuration = Configuration::factory()->create();

        $param = Param::create([
            "user_id" => $this->user->id,
            "configuration_id" => $configuration->id,
            "pi" => -0.50049837032537,
            "psi" => 0.086705270313224,
            "alpha" => 0.36951623165984,
            "tau" => 1.0,
            "kappa" => 1,
            "beta" => 0.0034437549399552,
            "search_terms" => json_encode([
                [
                    "term" => "black american",
                    "pi" => -0.424099419061494
                ],
                [
                    "term" => "proud boys stand",
                    "pi" => -0.483203551983422
                ]
            ]),
            "media_outlet_urls" => json_encode([
                [
                    "domain" => "cnbc.com",
                    "url" => "https://www.cnbc.com/",
                    "pi" => -0.0221973135909118
                ],
                [
                    "domain" => "theatlantic.com",
                    "url" => "https://www.theatlantic.com/",
                    "pi" => -0.417806599458759
                ]
            ])
        ]);

        $service = new ParamService(new Param());
        $result = $service->delete($param);

        $this->assertDatabaseMissing("params", $param->toArray());
        $this->assertInstanceOf(Collection::class, $result);

        foreach ($result as $param){
            $this->assertInstanceOf(Param::class, $param);
            $this->assertInstanceOf(User::class, $param->user);
            $this->assertInstanceOf(Configuration::class, $param->configuration);
        }
    }
}
