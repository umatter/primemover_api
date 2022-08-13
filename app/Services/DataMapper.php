<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use JsonMapper;

class DataMapper
{
    protected JsonMapper $jsonMapper;

    public function __construct(JsonMapper $jsonMapper)
    {
        $this->jsonMapper = $jsonMapper;
    }

    /**
     * @throws Exception
     */
    public function map(array $source, string $destinationClass): object
    {
        try {
            $destinationObject = App::make($destinationClass);

            return $this->jsonMapper->map($source, $destinationObject);
        } catch (Exception $e) {
            Log::error(
                '1594736977: Failed to map object',
                ['destinationClass' => $destinationClass, 'exception' => $e->getMessage()]
            );

            throw new Exception('Failed to map object');
        }
    }

    /**
     * @throws Exception
     */
    public function mapList(array $sourceList, string $destinationClass): Collection
    {
        try {
            return $this->jsonMapper->mapArray($sourceList, collect(), $destinationClass);
        } catch (Exception $e) {
            Log::error(
                '1594736978: Failed to map object list',
                ['destinationClass' => $destinationClass, 'exception' => $e->getMessage()]
            );

            throw new Exception('Failed to map object list');
        }
    }

    public function disableEnforceType(): self
    {
        $this->jsonMapper->bEnforceMapType = false;

        return $this;
    }

    public function removeUndefinedAttributes(): self
    {
        $this->jsonMapper->bRemoveUndefinedAttributes = true;

        return $this;
    }
}
