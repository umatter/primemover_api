<?php

namespace App\Modules\Experiment\Model\DTO;

use App\Modules\Crawler\Model\Crawler;
use App\Modules\Queue\Model\Queue;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class QueueDto
{
    public Crawler|Model|null $crawler = null;

    /**
     * @var Model|Queue|null
     */
    public mixed $CrawlerQueue = null;

    public ?string $name = null;

    public ?string $description = null;

    public ?Carbon $start_at = null;

    /**
     * @var JobDto[]
     */
    public ?array $jobs = null;

    private ?bool $active = true;

    private ?bool $new = true;

    public ?int $user_id = null;

    public function getNew(): ?bool
    {
        return $this->new;
    }

    public function setNew(?bool $new): QueueDto
    {
        $this->new = $new;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): QueueDto
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): QueueDto
    {
        $this->description = $description;

        return $this;
    }

    public function getStartAt(): ?Carbon
    {
        return $this->start_at;
    }

    public function setStartAt(?Carbon $start_at): QueueDto
    {
        $this->start_at = $start_at;

        return $this;
    }

    /**
     * @return JobDto[]|null
     */
    public function getJobs(): ?array
    {
        return $this->jobs;
    }

    /**
     * @param  JobDto[]|null  $jobs
     */
    public function setJobs(?array $jobs): QueueDto
    {
        $this->jobs = $jobs;

        return $this;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(?bool $active): QueueDto
    {
        $this->active = $active;

        return $this;
    }

    public function getCrawler(): Model|Crawler|null
    {
        return $this->crawler;
    }

    public function setCrawler(Model|Crawler|null $crawler): QueueDto
    {
        $this->crawler = $crawler;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function setUserId(?int $user_id): QueueDto
    {
        $this->user_id = $user_id;

        return $this;
    }

    public function getCrawlerQueue(): Queue|Model|null
    {
        return $this->CrawlerQueue;
    }

    public function setCrawlerQueue(Queue|Model|null $CrawlerQueue): QueueDto
    {
        $this->CrawlerQueue = $CrawlerQueue;

        return $this;
    }

}
