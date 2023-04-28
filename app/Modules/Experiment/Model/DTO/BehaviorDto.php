<?php

namespace App\Modules\Experiment\Model\DTO;

class BehaviorDto
{
    public ?string $name = null;

    public ?string $description = null;

    public ?string $value = null;

    public ?bool $active = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): BehaviorDto
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): BehaviorDto
    {
        $this->description = $description;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): BehaviorDto
    {
        $this->value = $value;

        return $this;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(?bool $active): BehaviorDto
    {
        $this->active = $active;

        return $this;
    }
}
