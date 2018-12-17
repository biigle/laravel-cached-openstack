<?php

namespace Biigle\CachedOpenStack;

use DateTime;
use OpenStack\Identity\v3\Service;
use OpenStack\Common\Api\ApiInterface;
use OpenStack\Common\Api\ClientInterface;
use Illuminate\Cache\CacheManager as Cache;

class CachedIdentityService extends Service
{
   /**
    * The cache instance to use.
    *
    * @var Cache
    */
   protected $cache;

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
      // Do not consider any old cached token for the cache key.
      unset($options['cachedToken']);
      $key = 'openstack-token-'.md5(json_encode($options));

      if ($this->cache->has($key)) {
         return $this->cache->get($key);
      }

      $authOptions = array_intersect_key($options, $this->api->postTokens()['params']);
      $token = $this->generateToken($authOptions);
      $cachedToken = $token->export();
      $this->cache->put($key, $cachedToken, new DateTime($cachedToken['expires_at']));

      return $cachedToken;
   }
}
