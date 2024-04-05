<?php

namespace App\Modules\Experiment\Pipe\Upload;

use App\Modules\Experiment\Model\DTO\CrawlerDto;
use App\Modules\Param\Model\Param;
use Closure;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class CreateConfigurationParams
{
    /**
     * @throws Exception
     */
    public function handle(CrawlerDto $crawlerDto, Closure $next): mixed
    {
        try {
            $dto = $crawlerDto
                ->setUserId(Auth::id())
                ->getConfiguration()
                ->getParams();

            Param::query()->create([
                'pi' => $dto->getPi(),
                'psi' => $dto->getPsi(),
                'alpha' => $dto->getAlpha(),
                'tau' => $dto->getTau(),
                'kappa' => $dto->getKappa(),
                'beta' => $dto->getBeta(),
                'user_id' => $crawlerDto->getUserId(),
                'configuration_id' => $crawlerDto->getCrawlerConfiguration()->id,
                'search_terms' => $dto->getSearchTermsEncoded(),
                'media_outlet_urls' => $dto->getMediaOutletUrlsEncoded(),
            ]);

            Log::info('1682672275: Configuration params created');

        } catch (Throwable $e) {
            throw new Exception('Configuration params could not be created', 422, $e);
        }

        return $next($crawlerDto);
    }
}
