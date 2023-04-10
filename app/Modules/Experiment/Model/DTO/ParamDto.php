<?php

namespace App\Modules\Experiment\Model\DTO;

class ParamDto
{
    public ?string $pi = null;

    public ?string $psi = null;

    public ?string $alpha = null;

    public ?string $tau = null;

    public ?string $kappa = null;

    public ?string $beta = null;

    /**
     * @var SearchTermDto[]
     */
    public ?array $search_terms = [];

    /**
     * @var MediaOutletUrlDto[]
     */
    public ?array $media_outlet_urls = [];

    private ?bool $active = true;

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(?bool $active): ParamDto
    {
        $this->active = $active;

        return $this;
    }

    public function getPi(): ?string
    {
        return $this->pi;
    }

    public function setPi(?string $pi): ParamDto
    {
        $this->pi = $pi;

        return $this;
    }

    public function getPsi(): ?string
    {
        return $this->psi;
    }

    public function setPsi(?string $psi): ParamDto
    {
        $this->psi = $psi;

        return $this;
    }

    public function getAlpha(): ?string
    {
        return $this->alpha;
    }

    public function setAlpha(?string $alpha): ParamDto
    {
        $this->alpha = $alpha;

        return $this;
    }

    public function getTau(): ?string
    {
        return $this->tau;
    }

    public function setTau(?string $tau): ParamDto
    {
        $this->tau = $tau;

        return $this;
    }

    public function getKappa(): ?string
    {
        return $this->kappa;
    }

    public function setKappa(?string $kappa): ParamDto
    {
        $this->kappa = $kappa;

        return $this;
    }

    public function getBeta(): ?string
    {
        return $this->beta;
    }

    public function setBeta(?string $beta): ParamDto
    {
        $this->beta = $beta;

        return $this;
    }

    public function getSearchTerms(): ?array
    {
        return $this->search_terms;
    }

    public function setSearchTerms(?array $search_terms): ParamDto
    {
        $this->search_terms = $search_terms;

        return $this;
    }

    public function getMediaOutletUrls(): ?array
    {
        return $this->media_outlet_urls;
    }

    public function setMediaOutletUrls(?array $media_outlet_urls): ParamDto
    {
        $this->media_outlet_urls = $media_outlet_urls;

        return $this;
    }
}
