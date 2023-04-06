<?php

namespace App\Modules\Experiment\Model\DTO;

use Carbon\Carbon;

class QueueDto
{
    public ?string $name = null;

    public ?string $description = null;

    public ?Carbon $start_at = null;

    public ?bool $active = null;

    /**
     * @var JobDto[]
     */
    public ?array $jobs = null;

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
}
