<?php

namespace App\Modules\Experiment\Model\DTO;

class MediaOutletUrlDto
{
    public ?string $domain = null;

    public ?string $url = null;

    public ?string $pi = null;

    private ?bool $active = true;

    public function getDomain(): ?string
    {
        return $this->domain;
    }

    public function setDomain(?string $domain): MediaOutletUrlDto
    {
        $this->domain = $domain;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): MediaOutletUrlDto
    {
        $this->url = $url;

        return $this;
    }

    public function getPi(): ?string
    {
        return $this->pi;
    }

    public function setPi(?string $pi): MediaOutletUrlDto
    {
        $this->pi = $pi;

        return $this;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(?bool $active): MediaOutletUrlDto
    {
        $this->active = $active;

        return $this;
    }
}
