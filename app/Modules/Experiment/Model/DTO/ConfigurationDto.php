<?php

namespace App\Modules\Experiment\Model\DTO;

use Spatie\LaravelData\Data;

class ConfigurationDto extends Data
{
    public ?string $name = null;

    public ?string $description = null;

    public ?ParamDto $params = null;

    /**
     * @var PreferenceDto[]
     */
    public ?array $preferences = [];

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): ConfigurationDto
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): ConfigurationDto
    {
        $this->description = $description;

        return $this;
    }

    public function getParams(): ?ParamDto
    {
        return $this->params;
    }

    public function setParams(?ParamDto $params): ConfigurationDto
    {
        $this->params = $params;

        return $this;
    }

    /**
     * @return PreferenceDto[]|null
     */
    public function getPreferences(): ?array
    {
        return $this->preferences;
    }

    /**
     * @param  PreferenceDto[]|null  $preferences
     */
    public function setPreferences(?array $preferences): ConfigurationDto
    {
        $this->preferences = $preferences;

        return $this;
    }
}
