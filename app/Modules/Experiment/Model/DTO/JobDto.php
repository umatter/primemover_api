<?php

namespace App\Modules\Experiment\Model\DTO;

class JobDto
{
    public ?string $name = null;

    public ?string $description = null;

    public ?string $type = null;

    /**
     * @var BehaviorDto[]
     */
    public ?array $behaviors = null;

    public ?bool $active = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): JobDto
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): JobDto
    {
        $this->description = $description;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): JobDto
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return BehaviorDto[]|null
     */
    public function getBehaviors(): ?array
    {
        return $this->behaviors;
    }

    /**
     * @param  BehaviorDto[]|null  $behaviors
     */
    public function setBehaviors(?array $behaviors): JobDto
    {
        $this->behaviors = $behaviors;

        return $this;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(?bool $active): JobDto
    {
        $this->active = $active;

        return $this;
    }
}
