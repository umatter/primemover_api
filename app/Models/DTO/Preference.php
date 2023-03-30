<?php

namespace App\Models\DTO;

class Preference
{
    protected ?int $id = null;

    protected ?string $name = '';

    protected ?string $description = '';

    protected ?string $value = '';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): Preference
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): Preference
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): Preference
    {
        $this->description = $description;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): Preference
    {
        $this->value = $value;

        return $this;
    }
}
