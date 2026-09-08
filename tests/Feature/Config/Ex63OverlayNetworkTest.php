<?php

namespace Tests\Feature\Config;

use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

/**
 * Every container that must reach the ex63 edge network has to say so in the
 * overlay, or the attachment survives only until the next `compose up -d`.
 *
 * The overlay attached `apache` from the start, because the symptom of missing
 * it is loud: traefik cannot route and the API is simply gone. `php` was left
 * out for far longer, because the symptom of missing IT is silent -- the
 * s3runner disk cannot reach MinIO, and GET /jobs/{id}/decision,
 * GET /file/{report} and decisions:backfill just stop serving any of the
 * ~71,800 stored objects while every other request keeps working.
 *
 * A hand-made `docker network connect` fixes it until a recreate reverts it.
 * This test asserts the overlay says it instead, which is the part that lasts.
 * It parses a file; it connects to nothing.
 *
 * @coversNothing
 */
class Ex63OverlayNetworkTest extends TestCase
{
    private const NETWORK = 'ex63_internal';

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    public static function containersNeedingTheEdgeNetwork(): array
    {
        return [
            'apache reaches IN' => ['apache', 'traefik cannot route to the API without it'],
            'php reaches OUT' => ['php', 'the s3runner disk cannot reach MinIO without it, and fails silently'],
        ];
    }

    /**
     * @dataProvider containersNeedingTheEdgeNetwork
     */
    public function test_the_overlay_puts_the_container_on_the_edge_network(string $service, string $why): void
    {
        $networks = $this->overlay()['services'][$service]['networks'] ?? null;

        $this->assertIsArray($networks, "the overlay should declare networks for {$service}");
        $this->assertContains(self::NETWORK, $networks, $why);
        $this->assertContains(
            'primemover-network',
            $networks,
            "{$service} still has to talk to the rest of the stack; listing networks REPLACES the base list"
        );
    }

    public function test_the_edge_network_is_external(): void
    {
        // The ex63-infra edge stack owns this network. Declaring it non-external
        // would make compose create a second, unrelated bridge of its own and
        // reach nothing, with no error.
        $network = $this->overlay()['networks'][self::NETWORK] ?? null;

        $this->assertIsArray($network, 'the overlay should declare the edge network');
        $this->assertTrue(
            (bool) ($network['external'] ?? false),
            'the edge network is created by ex63-infra and must be joined, not created'
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function overlay(): array
    {
        $path = base_path('docker-compose.ex63.yml');
        $this->assertFileExists($path, 'the ex63 overlay should exist');

        return Yaml::parseFile($path);
    }
}
