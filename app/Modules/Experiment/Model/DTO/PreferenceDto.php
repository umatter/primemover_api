<?php

namespace App\Modules\Experiment\Model\DTO;

class PreferenceDto
{
    public ?string $name = null;

    public ?string $value = null;

    private ?string $description = null;

    private ?bool $active = true;

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(?bool $active): PreferenceDto
    {
        $this->active = $active;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description = ''): PreferenceDto
    {
        $this->description = $description;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): PreferenceDto
    {
        $this->name = $name;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): PreferenceDto
    {
        $this->value = $value;

        return $this;
    }
}
