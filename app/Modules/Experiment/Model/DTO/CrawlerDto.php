<?php

namespace App\Modules\Experiment\Model\DTO;

use App\Modules\Agent\Model\Agent;
use App\Modules\Configuration\Model\Configuration;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Proxy\Model\Proxy;
use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelData\Data;

class CrawlerDto extends Data
{
    public Experiment|Model|null $experiment = null;

    public ?string $name = null;

    public ?string $description = null;

    public ?ConfigurationDto $configuration = null;

    public ?ProxyDto $proxy = null;

    public ?int $user_id = null;

    /**
     * @var Model|Configuration|null
     */
    public mixed $crawlerConfiguration = null;

    /**
     * @var Model|Proxy|null
     */
    public mixed $crawlerProxy = null;

    /**
     * @var Model|Crawler|null
     */
    public mixed $experimentCrawler = null;

    /**
     * @var QueueDto[]
     */
    public ?array $queues = [];

    private ?bool $updated = true;

    private ?bool $new = true;

    private ?bool $testing = false;

    private ?bool $active = true;

    /**
     * @var Model|Agent|null
     */
    private mixed $crawlerAgent = null;

    private ?AgentDto $agent = null;

    public function getUpdated(): ?bool
    {
        return $this->updated;
    }

    public function setUpdated(?bool $updated): CrawlerDto
    {
        $this->updated = $updated;

        return $this;
    }

    public function getNew(): ?bool
    {
        return $this->new;
    }

    public function setNew(?bool $new): CrawlerDto
    {
        $this->new = $new;

        return $this;
    }

    public function getCrawlerConfiguration(): Model|Configuration|null
    {
        return $this->crawlerConfiguration;
    }

    public function setCrawlerConfiguration(Model|Configuration|null $crawlerConfiguration): CrawlerDto
    {
        $this->crawlerConfiguration = $crawlerConfiguration;

        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function setUserId(?int $user_id): CrawlerDto
    {
        $this->user_id = $user_id;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): CrawlerDto
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): CrawlerDto
    {
        $this->description = $description;

        return $this;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(?bool $active): CrawlerDto
    {
        $this->active = $active;

        return $this;
    }

    public function getTesting(): ?bool
    {
        return $this->testing;
    }

    public function setTesting(?bool $testing): CrawlerDto
    {
        $this->testing = $testing;

        return $this;
    }

    public function getConfiguration(): ?ConfigurationDto
    {
        return $this->configuration;
    }

    public function setConfiguration(?ConfigurationDto $configuration): CrawlerDto
    {
        $this->configuration = $configuration;

        return $this;
    }

    public function getProxy(): ?ProxyDto
    {
        return $this->proxy;
    }

    public function setProxy(?ProxyDto $proxy): CrawlerDto
    {
        $this->proxy = $proxy;

        return $this;
    }

    /**
     * @return QueueDto[]|null
     */
    public function getQueues(): ?array
    {
        return $this->queues;
    }

    /**
     * @param  QueueDto[]|null  $queues
     */
    public function setQueues(?array $queues): CrawlerDto
    {
        $this->queues = $queues;

        return $this;
    }

    public function getAgent(): ?AgentDto
    {
        return $this->agent;
    }

    public function setAgent(?AgentDto $agent): CrawlerDto
    {
        $this->agent = $agent;

        return $this;
    }

    public function getCrawlerProxy(): mixed
    {
        return $this->crawlerProxy;
    }

    public function setCrawlerProxy(mixed $crawlerProxy): CrawlerDto
    {
        $this->crawlerProxy = $crawlerProxy;

        return $this;
    }

    public function getCrawlerAgent(): mixed
    {
        return $this->crawlerAgent;
    }

    public function setCrawlerAgent(mixed $crawlerAgent): CrawlerDto
    {
        $this->crawlerAgent = $crawlerAgent;

        return $this;
    }

    public function getExperiment(): Model|Experiment|null
    {
        return $this->experiment;
    }

    public function setExperiment(Model|Experiment|null $experiment): CrawlerDto
    {
        $this->experiment = $experiment;

        return $this;
    }

    public function getExperimentCrawler(): Crawler|Model|null
    {
        return $this->experimentCrawler;
    }

    public function setExperimentCrawler(Crawler|Model|null $experimentCrawler): CrawlerDto
    {
        $this->experimentCrawler = $experimentCrawler;

        return $this;
    }
}
