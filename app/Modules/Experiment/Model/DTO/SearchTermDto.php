<?php

namespace App\Modules\Experiment\Model\DTO;

class SearchTermDto
{
    public ?string $term = null;

    public ?string $pi = null;

    private ?bool $active = true;

    public function getTerm(): ?string
    {
        return $this->term;
    }

    public function setTerm(?string $term): SearchTermDto
    {
        $this->term = $term;

        return $this;
    }

    public function getPi(): ?string
    {
        return $this->pi;
    }

    public function setPi(?string $pi): SearchTermDto
    {
        $this->pi = $pi;

        return $this;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(?bool $active): SearchTermDto
    {
        $this->active = $active;

        return $this;
    }
}
