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

    public ?string $timezone = null;

    public ?string $timezone_utc_offset = null;

    /**
     * @var JobDto[]
     */
    public ?array $jobs = null;

    public ?int $user_id = null;

    private ?bool $active = true;

    private ?bool $new = true;

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
     * @param JobDto[]|null $jobs
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

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function setTimezone(?string $timezone): QueueDto
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function getTimezoneUtcOffset(): ?string
    {
        return $this->timezone_utc_offset;
    }

    public function setTimezoneUtcOffset(?string $timezone_utc_offset): QueueDto
    {
        $this->timezone_utc_offset = $timezone_utc_offset;

        return $this;
    }
}
