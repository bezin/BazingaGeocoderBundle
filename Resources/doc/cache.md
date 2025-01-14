# Caching the geocoder response

*[<< Back to documentation index](/Resources/doc/index.md)*

It is quite rare that a location response gets updated. That is why it is a good idea to cache the responses. The second
request will be both quicker and free of charge. To get started with caching you may use the `CachePlugin` which is supported
by default in our configuration.

```yaml
# config/packages/bazinga_geocoder.yaml
bazinga_geocoder:
    providers:
        acme:
            factory: Bazinga\GeocoderBundle\ProviderFactory\GoogleMapsFactory
            cache: 'any.psr16.service'
            cache_lifetime: 3600
            cache_precision: ~
```

If you do a lot of reverse queries it can be useful to cache them with less precision. So if you are interested in only the city,
you don't want to query for every exact coordinate. Instead, you want to remove some precision of the coordinates. For example:
reversing `52.3705273, 4.891031` will be cached as `52.3705, 4.8910`.

You may use any [PSR16](http://www.php-fig.org/psr/psr-16/) cache [implementation](https://packagist.org/providers/psr/simple-cache-implementation).
The `CachePlugin` helps you to cache all responses.

## Decorator pattern

If you do not like using the `CachePlugin` for some reason you may use the [`CacheProvider`](https://github.com/geocoder-php/cache-provider).
The `CacheProvider` is using the [Decorator pattern](https://en.wikipedia.org/wiki/Decorator_pattern) to do caching. Which
means that you wrap the `CacheProvider` around your existing provider.

```bash
composer require geocoder-php/cache-provider
```

```yaml
# config/packages/bazinga_geocoder.yaml
bazinga_geocoder:
    providers:
        acme:
            factory: Bazinga\GeocoderBundle\ProviderFactory\GoogleMapsFactory
```

```yaml
# config/services.yaml
services:
    my_cached_geocoder:
        class: Geocoder\Provider\Cache\ProviderCache
        arguments: ['@bazinga_geocoder.provider.acme', '@any.psr16.service', 3600]
```

## Installing a PSR16 cache

You may use any adapter from [PHP-cache.com](http://www.php-cache.com/en/latest/) or `symfony/cache`.

## Using Symfony cache

Symfony>=3.3 supports SimpleCache thanks to `Symfony\Component\Cache\Simple\Psr6Cache` adapter.
Thus a service can be registered like so:

```yaml
# config/services.yaml
app.simple_cache:
    class: Symfony\Component\Cache\Simple\Psr6Cache
    arguments: ['@app.cache.acme']
```

Then configure the framework and the bundle:

```yaml
# config/packages/cache.yaml
framework:
    cache:
        app: cache.adapter.redis
        pools:
            app.cache.acme:
                adapter: cache.app
                default_lifetime: 600

# config/packages/bazinga_geocoder.yaml
bazinga_geocoder:
    providers:
        my_google_maps:
            factory: Bazinga\GeocoderBundle\ProviderFactory\GoogleMapsFactory
            cache: 'app.simple_cache'
            cache_lifetime: 3600
            cache_precision: 4
```

For older Symfony version, you can use a bridge between PSR-6 and PSR-16. Install the
[bridge](https://github.com/php-cache/simple-cache-bridge) by:

```bash
composer require cache/simple-cache-bridge
```

Then, in the service declaration you can use `Cache\Bridge\SimpleCache\SimpleCacheBridge` instead of `Symfony\Component\Cache\Simple\Psr6Cache`.
