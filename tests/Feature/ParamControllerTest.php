<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Param\Http\Controller\Api\v1\ParamController;
use App\Modules\Param\Model\Param;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Tests\TestCase;

class ParamControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    
    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /** @test */
    public function testIndex()
    {
        $controller = new ParamController();
        $result = $controller->index();

        $this->assertInstanceOf(ResourceCollection::class, $result);

        foreach($result->resource as $param)
            $this->assertInstanceOf(Param::class, $param);
    }

    /** @test */
    public function testStore()
    {
        $data = [
            "pi" => -0.50049837032537,
            "psi" => 0.086705270313224,
            "alpha" => 0.36951623165984,
            "tau" => 1.0,
            "kappa" => 1,
            "beta" => 0.0034437549399552,
            "configuration_id" => 1,
        ];

        $data["search_terms"] = json_encode([
            [
                "term" => "black american",
                "pi" => -0.424099419061494
            ],
            [
                "term" => "proued boys stand",
                "pi" => -0.424099419061495
            ],
            [
                "term" => "environmental protection",
                "pi" => -0.424099419061496
            ],
        ]);

        $data["media_outlet_urls"]  = json_encode([
            [
                "domain" => "cnbc.com",
                "url" => "https://www.cnbc.com/",
                "pi" => -0.0221973135909118
            ],
            [
                "domain" => "theatlantic.com",
                "url" => "https://www.theatlantic.com/",
                "pi" => -0.417806599458759
            ],
        ]);

        $request = new Request($data);

        $controller = new ParamController();
        $result = $controller->store($request);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Param::class, $result->resource);
        $this->assertDatabaseHas("params", $request->all());
    }

    /** @test */
    public function testShow()
    {
        $data = [
            "user_id" => $this->user->id,
            "pi" => -0.50049837032537,
            "psi" => 0.086705270313224,
            "alpha" => 0.36951623165984,
            "tau" => 1.0,
            "kappa" => 1,
            "beta" => 0.0034437549399552,
            "configuration_id" => 1,
        ];

        $data["search_terms"] = json_encode([
            [
                "term" => "black american",
                "pi" => -0.424099419061494
            ],
            [
                "term" => "proued boys stand",
                "pi" => -0.424099419061495
            ],
            [
                "term" => "environmental protection",
                "pi" => -0.424099419061496
            ],
        ]);

        $data["media_outlet_urls"]  = json_encode([
            [
                "domain" => "cnbc.com",
                "url" => "https://www.cnbc.com/",
                "pi" => -0.0221973135909118
            ],
            [
                "domain" => "theatlantic.com",
                "url" => "https://www.theatlantic.com/",
                "pi" => -0.417806599458759
            ],
        ]);

        $param = Param::create($data);

        $controller = new ParamController();
        $result = $controller->show($param->id);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Param::class, $result->resource);
        $this->assertEquals($param->id, $result->resource->id);
    }

    /** @test */
    public function testEdit()
    {
        $data = [
            "user_id" => $this->user->id,
            "pi" => -0.50049837032537,
            "psi" => 0.086705270313224,
            "alpha" => 0.36951623165984,
            "tau" => 1.0,
            "kappa" => 1,
            "beta" => 0.0034437549399552,
            "configuration_id" => 1,
        ];

        $data["search_terms"] = json_encode([
            [
                "term" => "black american",
                "pi" => -0.424099419061494
            ],
            [
                "term" => "proued boys stand",
                "pi" => -0.424099419061495
            ],
            [
                "term" => "environmental protection",
                "pi" => -0.424099419061496
            ],
        ]);

        $data["media_outlet_urls"]  = json_encode([
            [
                "domain" => "cnbc.com",
                "url" => "https://www.cnbc.com/",
                "pi" => -0.0221973135909118
            ],
            [
                "domain" => "theatlantic.com",
                "url" => "https://www.theatlantic.com/",
                "pi" => -0.417806599458759
            ],
        ]);

        $param = Param::create($data);

        $controller = new ParamController();
        $result = $controller->edit($param);

        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Param::class, $result->resource);
        $this->assertEquals($param->id, $result->resource->id);
    }

    /** @test */
    public function testUpdate()
    {
        $data = [
            "user_id" => $this->user->id,
            "pi" => -0.50049837032537,
            "psi" => 0.086705270313224,
            "alpha" => 0.36951623165984,
            "tau" => 1.0,
            "kappa" => 1,
            "beta" => 0.0034437549399552,
            "configuration_id" => 1,
            "search_terms" => json_encode([
                [
                    "term" => "black american update",
                    "pi" => -0.424099419061494
                ],
                [
                    "term" => "proued boys stand",
                    "pi" => -0.424099419061495
                ],
                [
                    "term" => "environmental protection",
                    "pi" => -0.424099419061496
                ],
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
                ],
            ])
        ];

        $request = new Request($data);

        $param = Param::create($data);

        $controller = new ParamController();
        $result = $controller->update($request, $param);

        $this->assertDatabaseHas("params", $data);
        $this->assertInstanceOf(JsonResource::class, $result);
        $this->assertInstanceOf(Param::class, $result->resource);
    }

    /** @test */
    public function destroy()
    {
        $data = [
            "user_id" => $this->user->id,
            "pi" => -0.50049837032537,
            "psi" => 0.086705270313224,
            "alpha" => 0.36951623165984,
            "tau" => 1.0,
            "kappa" => 1,
            "beta" => 0.0034437549399552,
            "configuration_id" => 1,
            "search_terms" => json_encode([
                [
                    "term" => "black american update",
                    "pi" => -0.424099419061494
                ],
                [
                    "term" => "proued boys stand",
                    "pi" => -0.424099419061495
                ],
                [
                    "term" => "environmental protection",
                    "pi" => -0.424099419061496
                ],
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
                ],
            ])
        ];

        $param = Param::create($data);

        $controller = new ParamController();
        $result = $controller->destroy($param);

        $this->assertDatabaseMissing("params", $param->toArray());
        $this->assertInstanceOf(ResourceCollection::class, $result);

        foreach($result->resource as $param) 
            $this->assertInstanceOf(Param::class, $param);
    }
}
