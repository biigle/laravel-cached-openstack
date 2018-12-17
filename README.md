# Cached OpenStack

A wrapper for the [OpenStack SDK](https://github.com/php-opencloud/openstack) that caches the authentication token. Works with Laravel and Lumen.

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
