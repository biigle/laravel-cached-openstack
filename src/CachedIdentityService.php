<?php

namespace Biigle\CachedOpenStack;

use DateInterval;
use DateTime;
use Illuminate\Cache\CacheManager as Cache;
use OpenStack\Common\Api\ApiInterface;
use OpenStack\Common\Api\ClientInterface;
use OpenStack\Identity\v3\Service;

class CachedIdentityService extends Service
{
    /**
     * The cache instance to use.
     *
     * @var Cache
     */
    protected $cache;

    /**
     * Options for caching.
     *
     * @var array
     */
    protected $cacheOptions;

    /**
     * Set the cache instance to use.
     *
     * @param Cache $cache
     */
    public function setCache(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Set the cache options to use.
     *
     * @param array $options
     */
    public function setCacheOptions(array $options)
    {
        $this->cacheOptions = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(array $options): array
    {
        $options['cachedToken'] = $this->getCachedToken($options);

        return parent::authenticate($options);
    }

    /**
     * Get the cached token.
     *
     * Creates a new cached token if the old one is expired or does not exist.
     *
     * @param array $options OpenStack options.
     *
     * @return array
     */
    protected function getCachedToken(array $options)
    {
        $authOptions = array_intersect_key($options, $this->api->postTokens()['params']);

        // Determine a unique key for the used authentication options. We add the authUrl
        // because it is possible to use the same credentials for a different OpenStack
        // instance, which should use a different authentication token.
        $optionsToHash = array_merge($authOptions, array_intersect_key($options, [
            'authUrl' => true,
        ]));
        // Do not include the password in the insecure hash.
        if (isset($optionsToHash['user'])) {
            unset($optionsToHash['user']['password']);
        }
        $key = 'openstack-token-'.md5(json_encode($optionsToHash));

        if ($this->cache->has($key)) {
            return $this->cache->get($key);
        }

        $token = $this->generateToken($authOptions);
        $cachedToken = $token->export();

        $expiresAt = new DateTime($cachedToken['expires_at']);
        // Cache the token for 1 minute less than it's considered valid to avoid the
        // edge case discussed here:
        // https://github.com/mzur/laravel-openstack-swift/issues/1
        $expiresAt = $expiresAt->sub(new DateInterval('PT1M'));

        if (is_array($this->cacheOptions) && array_key_exists('ttl', $this->cacheOptions)) {
            $seconds = $this->cacheOptions['ttl'];
            $ttl = new DateTime("+{$seconds} seconds");

            if ($ttl < $expiresAt) {
                $expiresAt = $ttl;
            }
        }

        $this->cache->put($key, $cachedToken, $expiresAt);

        return $cachedToken;
    }
}
