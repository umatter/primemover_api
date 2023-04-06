<?php

namespace App\Modules\Experiment\Model\DTO;

class ProxyDto
{
    public ?string $name = null;

    public ?string $description = null;

    public ?string $hostname = null;

    public ?int $port = null;

    public ?string $username = null;

    public ?string $password = null;

    public ?string $type = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): ProxyDto
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): ProxyDto
    {
        $this->description = $description;

        return $this;
    }

    public function getHostname(): ?string
    {
        return $this->hostname;
    }

    public function setHostname(?string $hostname): ProxyDto
    {
        $this->hostname = $hostname;

        return $this;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function setPort(?int $port): ProxyDto
    {
        $this->port = $port;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): ProxyDto
    {
        $this->username = $username;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): ProxyDto
    {
        $this->password = $password;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): ProxyDto
    {
        $this->type = $type;

        return $this;
    }
}
