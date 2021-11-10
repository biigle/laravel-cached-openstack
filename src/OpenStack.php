<?php

namespace Biigle\CachedOpenStack;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware as GuzzleMiddleware;
use Illuminate\Cache\CacheManager as Cache;
use OpenStack\Common\Service\Builder;
use OpenStack\Common\Transport\Utils;
use OpenStack\OpenStack as BaseOpenStack;

class OpenStack extends BaseOpenStack
{
    /**
     * Create a new instance.
     *
     * @param Cache $cache Cache instance to use.
     * @param array $options OpenStack options.
     * @param Builder|null $builder OpenStack service builder.
     */
    public function __construct(Cache $cache, array $options = [], Builder $builder = null)
    {
        $options['identityService'] = $this->getCachedIdentityService($cache, $options);
        parent::__construct($options, $builder);
    }

    /**
     * Create the cached identity serivce.
     *
     * @param Cache $cache
     * @param array $options
     *
     * @return CachedIdentityService
     */
    protected function getCachedIdentityService(Cache $cache, array $options): CachedIdentityService
    {
        if (!isset($options['authUrl'])) {
            throw new \InvalidArgumentException("'authUrl' is a required option");
        }

        $stack = HandlerStack::create();

        if (!empty($options['debugLog'])
            && !empty($options['logger'])
            && !empty($options['messageFormatter'])
      ) {
            $stack->push(GuzzleMiddleware::log($options['logger'], $options['messageFormatter']));
        }

        $clientOptions = [
         'base_uri' => Utils::normalizeUrl($options['authUrl']),
         'handler'  => $stack,
      ];

        if (isset($options['requestOptions'])) {
            $clientOptions = array_merge($options['requestOptions'], $clientOptions);
        }

        $service = CachedIdentityService::factory(new Client($clientOptions));
        $service->setCache($cache);

        if (array_key_exists('cacheOptions', $options)) {
            $service->setCacheOptions($options['cacheOptions']):
        }

        return $service;
    }
}
