# Cached OpenStack

A wrapper for the [OpenStack SDK](https://github.com/php-opencloud/openstack) that caches and renews the authentication token. Works with Laravel and Lumen.

The wrapper is specifically intended for use in long running daemon queue workers as it renews the cached authentication token automatically.

## Installation

```
composer require biigle/laravel-cached-openstack
```

## Usage

```php
use Biigle\CachedOpenStack\OpenStack;

$cache = app('cache');
$options = [
   // OpenStack options...
];

$openstack = new OpenStack($cache, $options);
```

### Options

You can include cache options in the OpenStack options array. Example:

```php
$options = [
   'cacheOptions' => [
      'ttl' => 3600,
   ],
];

$openstack = new OpenStack($cache, $options);
```

Available options:

- `ttl`: Overrides the duration that the authentication token should be cached in seconds. If not set, the token is cached until its `expires_at` [minus 60 seconds](https://github.com/mzur/laravel-openstack-swift/issues/1). If `expires_at` is less than the specified `ttl`, `ttl` is ignored.
