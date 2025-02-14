<?php

declare(strict_types=1);

/*
 * This file is part of the BazingaGeocoderBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Bazinga\GeocoderBundle\Doctrine\ORM;

use Bazinga\GeocoderBundle\Mapping\ClassMetadata;
use Bazinga\GeocoderBundle\Mapping\Driver\DriverInterface;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\UnitOfWork;
use Geocoder\Provider\Provider;
use Geocoder\Query\GeocodeQuery;

/**
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 */
class GeocoderListener implements EventSubscriber
{
    /**
     * @var DriverInterface
     */
    private $driver;

    /**
     * @var Provider
     */
    private $geocoder;

    public function __construct(Provider $geocoder, DriverInterface $driver)
    {
        $this->driver = $driver;
        $this->geocoder = $geocoder;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     * @phpstan-return list<string>
     */
    public function getSubscribedEvents()
    {
        return [
            Events::onFlush,
        ];
    }

    /**
     * @return void
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if (!$this->driver->isGeocodeable($entity)) {
                continue;
            }

            /** @var ClassMetadata $metadata */
            $metadata = $this->driver->loadMetadataFromObject($entity);

            $this->geocodeEntity($metadata, $entity);

            $uow->recomputeSingleEntityChangeSet(
                $em->getClassMetadata(get_class($entity)),
                $entity
            );
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (!$this->driver->isGeocodeable($entity)) {
                continue;
            }

            /** @var ClassMetadata $metadata */
            $metadata = $this->driver->loadMetadataFromObject($entity);

            if (!$this->shouldGeocode($metadata, $uow, $entity)) {
                continue;
            }

            $this->geocodeEntity($metadata, $entity);

            $uow->recomputeSingleEntityChangeSet(
                $em->getClassMetadata(get_class($entity)),
                $entity
            );
        }
    }

    /**
     * @param object $entity
     *
     * @return void
     */
    private function geocodeEntity(ClassMetadata $metadata, $entity)
    {
        if (null !== $metadata->addressGetter) {
            $address = $metadata->addressGetter->invoke($entity);
        } else {
            $address = $metadata->addressProperty->getValue($entity);
        }

        if (empty($address) || !is_string($address)) {
            return;
        }

        $results = $this->geocoder->geocodeQuery(GeocodeQuery::create($address));

        if (!$results->isEmpty()) {
            $result = $results->first();
            $metadata->latitudeProperty->setValue($entity, $result->getCoordinates()->getLatitude());
            $metadata->longitudeProperty->setValue($entity, $result->getCoordinates()->getLongitude());
        }
    }

    /**
     * @param object $entity
     */
    private function shouldGeocode(ClassMetadata $metadata, UnitOfWork $unitOfWork, $entity): bool
    {
        if (null !== $metadata->addressGetter) {
            return true;
        }

        $changeSet = $unitOfWork->getEntityChangeSet($entity);

        return isset($changeSet[$metadata->addressProperty->getName()]);
    }
}
