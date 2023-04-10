<?php

namespace App\Modules\Experiment\Model\DTO;

class AgentDto
{
    public ?string $name = null;

    public ?string $description = null;

    public ?string $identification = null;

    public ?string $multilogin_id = null;

    public ?string $multilogin_profile = null;

    public ?string $location = null;

    private ?bool $active = true;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): AgentDto
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): AgentDto
    {
        $this->description = $description;

        return $this;
    }

    public function getIdentification(): ?string
    {
        return $this->identification;
    }

    public function setIdentification(?string $identification): AgentDto
    {
        $this->identification = $identification;

        return $this;
    }

    public function getMultiloginId(): ?string
    {
        return $this->multilogin_id;
    }

    public function setMultiloginId(?string $multilogin_id): AgentDto
    {
        $this->multilogin_id = $multilogin_id;

        return $this;
    }

    public function getMultiloginProfile(): ?string
    {
        return $this->multilogin_profile;
    }

    public function setMultiloginProfile(?string $multilogin_profile): AgentDto
    {
        $this->multilogin_profile = $multilogin_profile;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): AgentDto
    {
        $this->location = $location;

        return $this;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(?bool $active): AgentDto
    {
        $this->active = $active;

        return $this;
    }
}
