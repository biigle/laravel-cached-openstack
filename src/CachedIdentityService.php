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
    * Create a new instance.
    *
    * @param Cache $cache
    * @param ClientInterface $client
    *
    * @return CachedIdentityService
    */
   public static function factory(Cache $cache, ClientInterface $client): self
   {
      return new static($cache, $client, new Api());
   }

   /**
    * Create a new instance.
    *
    * @param Cache $cache
    * @param ClientInterface $client
    * @param ApiInterface $api
    */
   public function __construct(Cache $cache, ClientInterface $client, ApiInterface $api)
   {
      parent::__construct($client, $api);
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
      $cache->put($key, $cachedToken, new DateTime($cachedToken['expires_at']));

      return $cachedToken;
   }
}
