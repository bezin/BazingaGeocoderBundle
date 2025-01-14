<?php

declare(strict_types=1);

/*
 * This file is part of the BazingaGeocoderBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Bazinga\GeocoderBundle\ProviderFactory;

use Geocoder\Provider\MaxMind\MaxMind;
use Geocoder\Provider\Provider;
use Http\Client\HttpClient;
use Http\Discovery\HttpClientDiscovery;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class MaxMindFactory extends AbstractFactory
{
    protected static $dependencies = [
        ['requiredClass' => MaxMind::class, 'packageName' => 'geocoder-php/maxmind-provider'],
    ];

    /**
     * @phpstan-param array{api_key: string, endpoint: string, httplug_client: ?HttpClient} $config
     */
    protected function getProvider(array $config): Provider
    {
        $httplug = $config['httplug_client'] ?: $this->httpClient ?? HttpClientDiscovery::find();

        return new MaxMind($httplug, $config['api_key'], $config['endpoint']);
    }

    protected static function configureOptionResolver(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'httplug_client' => null,
            'endpoint' => MaxMind::CITY_EXTENDED_SERVICE,
        ]);

        $resolver->setRequired('api_key');
        $resolver->setAllowedTypes('httplug_client', ['object', 'null']);
        $resolver->setAllowedTypes('api_key', ['string']);
        $resolver->setAllowedValues('endpoint', [MaxMind::CITY_EXTENDED_SERVICE, MaxMind::OMNI_SERVICE]);
    }
}
