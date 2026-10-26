<?php

namespace App\Modules\Crawler;

use App\Modules\Agent\Model\Agent;
use App\Modules\Configuration\Model\Configuration;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Proxy\Model\Proxy;

/**
 * Deletes the Library items of crawlers that are being destroyed -- but only
 * the ones no remaining crawler uses.
 *
 * crawler -> agent/proxy/configuration is not unique: one item can sit under
 * crawlers in several studies, and the destroy routes used to delete each
 * crawler's items outright, taking another study's browser profile, exit IP
 * or treatment with them.
 *
 * Collect the ids BEFORE deleting the crawlers, then delete the crawlers, then
 * call deleteUnused(). Like the crawler's setup relations it replaces, it
 * reaches active items only; inactive ones were never deleted.
 */
final class SetupCleanup
{
    /**
     * @param array<int, int> $agentIds
     * @param array<int, int> $proxyIds
     * @param array<int, int> $configurationIds
     */
    private function __construct(
        private array $agentIds,
        private array $proxyIds,
        private array $configurationIds,
    ) {}

    /** @param iterable<Crawler> $crawlers */
    public static function of(iterable $crawlers): self
    {
        $agents = $proxies = $configurations = [];
        foreach ($crawlers as $crawler) {
            $agents[] = $crawler->agent_id;
            $proxies[] = $crawler->proxy_id;
            $configurations[] = $crawler->configuration_id;
        }

        $ids = fn (array $list): array => array_values(array_unique(array_filter($list, fn ($id) => null !== $id)));

        return new self($ids($agents), $ids($proxies), $ids($configurations));
    }

    public function deleteUnused(): void
    {
        foreach ([
            [Agent::class, 'agent_id', $this->agentIds],
            [Proxy::class, 'proxy_id', $this->proxyIds],
            [Configuration::class, 'configuration_id', $this->configurationIds],
        ] as [$model, $column, $ids]) {
            if ([] === $ids) {
                continue;
            }

            $stillUsed = Crawler::query()->whereIn($column, $ids)->pluck($column)->all();

            $model::query()
                ->whereIn('id', array_diff($ids, $stillUsed))
                ->where('active', 1)
                ->delete();
        }
    }
}
