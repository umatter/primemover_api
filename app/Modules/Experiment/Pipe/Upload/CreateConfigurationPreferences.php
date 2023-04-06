<?php

namespace App\Modules\Experiment\Pipe\Upload;

use App\Modules\Experiment\Model\DTO\CrawlerDto;
use App\Modules\Experiment\Model\DTO\PreferenceDto;
use Closure;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Throwable;

class CreateConfigurationPreferences
{
    /**
     * @throws Exception
     */
    public function handle(CrawlerDto $crawlerDto, Closure $next): mixed
    {
        try {
            $preferences = $crawlerDto
                ->setUserId(Auth::id())
                ->getConfiguration()
                ->getPreferences();

            Collection::make($preferences)->map(function (PreferenceDto $dto) use ($crawlerDto): void {
                $crawlerDto
                    ->getCrawlerConfiguration()
                    ->preferences()
                    ->create([
                        'name' => $dto->getName(),
                        'description' => $dto->getDescription(),
                        'value' => $dto->getValue(),
                        'user_id' => $crawlerDto->getUserId(),
                    ]);
            });

        } catch (Throwable $e) {
            throw new Exception('Configuration preferences could not be created', 422, $e);
        }

        return $next($crawlerDto);
    }
}
