<?php

namespace Tests\Feature;

use App\Modules\Agent\Model\Agent;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * POST agents keeps the Multilogin fields whichever spelling the client uses.
 *
 * prepareForValidation() used to overwrite multilogin_profile / multilogin_id
 * from the camelCase inputs, so a client sending the model's own field names
 * had both stored as null.
 *
 * @internal
 *
 * @coversDefaultClass \App\Modules\Agent\Http\Request\AgentStoreRequest
 */
class AgentStoreMultiloginTest extends TestCase
{
    /**
     * @covers ::prepareForValidation
     */
    public function test_snake_case_fields_are_stored(): void
    {
        Passport::actingAs($this->user);

        $id = $this->postJson('/api/v1/agents', [
            'name' => 'E2E YouTube Agent',
            'identification' => 'mlx',
            'multilogin_id' => 'abc-123',
            'multilogin_profile' => '{"browser_type": "mimic"}',
        ])->assertCreated()->json('data.id');

        $agent = Agent::findOrFail($id);
        $this->assertSame('abc-123', $agent->multilogin_id);
        $this->assertSame('{"browser_type": "mimic"}', $agent->multilogin_profile);
    }

    /**
     * @covers ::prepareForValidation
     */
    public function test_camel_case_fields_still_work(): void
    {
        Passport::actingAs($this->user);

        $id = $this->postJson('/api/v1/agents', [
            'name' => 'legacy client',
            'identification' => 'mlx',
            'multiLoginId' => 'def-456',
            'multiLoginProfile' => '{"os_type": "linux"}',
        ])->assertCreated()->json('data.id');

        $agent = Agent::findOrFail($id);
        $this->assertSame('def-456', $agent->multilogin_id);
        $this->assertSame('{"os_type": "linux"}', $agent->multilogin_profile);
    }
}
