<?php

/**
 * This file is part of the Phalcon Framework.
 *
 * (c) Phalcon Team <team@phalcon.io>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phalcon\Incubator\MongoDB\Mvc\Collection;

use MongoDB\Database;
use Phalcon\Events\ManagerInterface as EventsManagerInterface;
use Phalcon\Incubator\MongoDB\Mvc\CollectionInterface;

/**
 * Phalcon\Mvc\Collection\Manager
 *
 * This components controls the initialization of collections, keeping record of relations
 * between the different collections of the application.
 *
 * A CollectionManager is injected to a collection via a Dependency Injector Container such as Phalcon\Di\Di.
 *
 * <code>
 * $di = new \Phalcon\Di\Di\Di();
 *
 * $di->set(
 *     "collectionManager",
 *     function() {
 *         return new \Phalcon\Incubator\Mvc\Collection\Manager();
 *     }
 * );
 *
 * $robot = new Robots(di);
 * </code>
 */
interface ManagerInterface
{
    /**
     * Sets a custom events manager for a specific collection
     *
     * @param CollectionInterface $collection
     * @param EventsManagerInterface $eventsManager
     * @return mixed
     */
    public function setCustomEventsManager(CollectionInterface $collection, EventsManagerInterface $eventsManager);

    /**
     * Returns a custom events manager related to a collection
     *
     * @param CollectionInterface $collection
     * @return EventsManagerInterface
     */
    public function getCustomEventsManager(CollectionInterface $collection): ?EventsManagerInterface;

    /**
     * Initializes a collection in the collections manager
     *
     * @param CollectionInterface $collection
     * @return mixed
     */
    public function initialize(CollectionInterface $collection);

    /**
     * Check whether a collection is already initialized
     *
     * @param string $collectionName
     * @return bool
     */
    public function isInitialized(string $collectionName): bool;

    /**
     * Get the latest initialized collection
     *
     * @return CollectionInterface
     */
    public function getLastInitialized(): CollectionInterface;

    /**
     * Sets a connection service for a specific collection
     *
     * @param CollectionInterface $collection
     * @param string $connectionService
     * @return mixed
     */
    public function setConnectionService(CollectionInterface $collection, string $connectionService);

    /**
     * Sets if a collection must use implicit objects ids
     *
     * @param CollectionInterface $collection
     * @param bool $useImplicitObjectIds
     * @return mixed
     */
    public function useImplicitObjectIds(CollectionInterface $collection, bool $useImplicitObjectIds);

    /**
     * Checks if a collection is using implicit object ids
     *
     * @param CollectionInterface $collection
     * @return bool
     */
    public function isUsingImplicitObjectIds(CollectionInterface $collection): bool;

    /**
     * Returns the connection related to a collection
     *
     * @param CollectionInterface $collection
     * @return mixed|Database
     */
    public function getConnection(CollectionInterface $collection);

    /**
     * Sets the mapped source for a collection
     *
     * @param CollectionInterface $collection
     * @param string $source
     */
    public function setCollectionSource(CollectionInterface $collection, string $source): void;

    /**
     * Returns the mapped source for a collection
     *
     * @param CollectionInterface $collection
     * @return string
     */
    public function getCollectionSource(CollectionInterface $collection): string;

    /**
     * Receives events generated in the collections and dispatches them to an events-manager if available
     * Notify the behaviors that are listening in the collection
     *
     * @param string $eventName
     * @param CollectionInterface $collection
     * @return mixed
     */
    public function notifyEvent(string $eventName, CollectionInterface $collection);

    /**
     * Binds a behavior to a collection
     *
     * @param CollectionInterface $collection
     * @param BehaviorInterface $behavior
     * @return mixed
     */
    public function addBehavior(CollectionInterface $collection, BehaviorInterface $behavior);
}
