<?php

/** @noinspection PhpUnused */

/**
 * This file is part of the Phalcon Framework.
 *
 * (c) Phalcon Team <team@phalconphp.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phalcon\Incubator\MongoDB\Mvc\Collection;

use MongoDB\Database;
use Phalcon\Di\DiInterface;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\Events\EventsAwareInterface;
use Phalcon\Events\ManagerInterface as EventsManagerInterface;
use Phalcon\Helper\Str;
use Phalcon\Incubator\MongoDB\Mvc\CollectionInterface;

use function Phalcon\Incubator\MongoDB\get_class_lower;
use function Phalcon\Incubator\MongoDB\get_class_ns;

/**
 * Phalcon\Incubator\MongoDB\Mvc\Collection\Manager
 *
 * This components controls the initialization of collections, keeping record of relations
 * between the different collections of the application.
 *
 * A CollectionManager is injected to a collection via a Dependency Injector Container such as Phalcon\Di.
 *
 * <code>
 * $di = new \Phalcon\Di();
 *
 * $di->set(
 *     "collectionManager",
 *     function () {
 *         return new \Phalcon\Incubator\MongoDB\Mvc\Collection\Manager();
 *     }
 * );
 *
 * $robot = new Robots($di);
 * </code>
 *
 * @package Phalcon\Incubator\MongoDB\Mvc\Collection
 */
class Manager implements ManagerInterface, InjectionAwareInterface, EventsAwareInterface
{
    protected $container;

    protected $eventsManager;

    protected $initialized = [];

    protected $sources = [];

    protected $behaviors = null;

    protected $prefix = "";

    protected $serviceName = 'mongo';

    protected $implicitObjectsIds = [];

    /** @var array|null $connectionServices */
    protected $connectionServices = null;

    /** @var ManagerInterface|null $customEventsManager */
    protected $customEventsManager = null;

    /** @var CollectionInterface|null $lastInitialized */
    protected $lastInitialized = null;

    /**
     * Sets the DependencyInjector container
     *
     * @param DiInterface $container
     */
    public function setDI(DiInterface $container): void
    {
        $this->container = $container;
    }

    /**
     * Returns the DependencyInjector container
     *
     * @return DiInterface
     */
    public function getDI(): DiInterface
    {
        return $this->container;
    }

    /**
     * Sets the event manager
     *
     * @param EventsManagerInterface $eventsManager
     */
    public function setEventsManager(EventsManagerInterface $eventsManager): void
    {
        $this->eventsManager = $eventsManager;
    }

    /**
     * Returns the internal event manager
     *
     * @return EventsManagerInterface
     */
    public function getEventsManager(): EventsManagerInterface
    {
        return $this->eventsManager;
    }

    /**
     * @param CollectionInterface $collection
     * @param string $source
     */
    public function setCollectionSource(CollectionInterface $collection, string $source): void
    {
        $this->sources[get_class_lower($collection)] = $source;
    }

    /**
     * @param CollectionInterface $collection
     * @return string
     */
    public function getCollectionSource(CollectionInterface $collection): string
    {
        $entityName = get_class_lower($collection);

        if (!isset($this->sources[$entityName])) {
            $this->setCollectionSource(
                $collection,
                Str::uncamelize(get_class_ns($collection))
            );
        }

        return $this->prefix . $this->sources[$entityName];
    }

    /**
     * Sets a custom events manager for a specific collection
     *
     * @param CollectionInterface $collection
     * @param EventsManagerInterface $eventsManager
     */
    public function setCustomEventsManager(CollectionInterface $collection, EventsManagerInterface $eventsManager): void
    {
        $this->customEventsManager[get_class($collection)] = $eventsManager;
    }

    /**
     * Returns a custom events manager related to a collection
     *
     * @param CollectionInterface $collection
     * @return mixed|null
     */
    public function getCustomEventsManager(CollectionInterface $collection): ?EventsManagerInterface
    {
        if (is_array($this->customEventsManager)) {
            $className = get_class_lower($collection);

            if (isset($this->customEventsManager[$className])) {
                return $this->customEventsManager[$className];
            }
        }

        return null;
    }

    /**
     * Initializes a collection in the collections manager
     *
     * @param CollectionInterface $collection
     */
    public function initialize(CollectionInterface $collection): void
    {
        $className = get_class($collection);

        /**
         * Collections are just initialized once per request
         */
        if (!isset($this->initialized[$className])) {
            if (method_exists($collection, 'initialize')) {
                $collection->initialize();
            }

            /**
             * If an EventsManager is available we pass to it every initialized collection
             */
            if (is_object($this->eventsManager)) {
                $this->eventsManager->fire('collectionManager:afterInitialize', $collection);
            }

            $this->initialized[$className] = $collection;
            $this->lastInitialized = $collection;
        }
    }

    /**
     * Check whether a collection is already initialized
     *
     * @param string $collectionName
     * @return bool
     */
    public function isInitialized(string $collectionName): bool
    {
        return isset($this->initialized[strtolower($collectionName)]);
    }

    /**
     * Get the latest initialized collection
     *
     * @return CollectionInterface
     */
    public function getLastInitialized(): CollectionInterface
    {
        return $this->lastInitialized;
    }

    /**
     * Sets a connection service for a specific collection
     *
     * @param CollectionInterface $collection
     * @param string $connectionService
     */
    public function setConnectionService(CollectionInterface $collection, string $connectionService): void
    {
        $this->connectionServices[get_class($collection)] = $connectionService;
    }

    /**
     * Gets a connection service for a specific collection
     *
     * @param CollectionInterface $collection
     * @return string
     */
    public function getConnectionService(CollectionInterface $collection): string
    {
        $service = $this->serviceName;
        $entityName = get_class($collection);

        if (isset($this->connectionServices[$entityName])) {
            $service = $this->connectionServices[$entityName];
        }

        return $service;
    }

    /**
     * Sets whether a collection must use implicit objects ids
     *
     * @param CollectionInterface $collection
     * @param bool $useImplicitObjectIds
     */
    public function useImplicitObjectIds(CollectionInterface $collection, bool $useImplicitObjectIds): void
    {
        $this->implicitObjectsIds[get_class($collection)] = $useImplicitObjectIds;
    }

    /**
     * Checks if a collection is using implicit object ids
     *
     * @param CollectionInterface $collection
     * @return bool
     */
    public function isUsingImplicitObjectIds(CollectionInterface $collection): bool
    {
        /**
         * All collections use by default are using implicit object ids
         */
        if (isset($this->implicitObjectsIds[get_class($collection)])) {
            return $this->implicitObjectsIds[get_class($collection)];
        }

        return true;
    }

    /**
     * Returns the connection related to a collection
     *
     * @param CollectionInterface $collection
     * @return mixed|Database
     * @throws Exception
     */
    public function getConnection(CollectionInterface $collection)
    {
        $service = $this->serviceName;

        if (is_array($this->connectionServices)) {
            $entityName = get_class($collection);

            /**
             * Check if the collection has a custom connection service
             */
            if (isset($this->connectionServices[$entityName])) {
                $service = $this->connectionServices[$entityName];
            }
        }

        if (!is_object($this->container)) {
            throw new Exception(
                'A dependency injector container is required to obtain the services related to the ORM'
            );
        }

        $connection = $this->container->getShared($service);

        if (!is_object($connection)) {
            throw new Exception('Invalid injected connection service');
        }

        return $connection;
    }

    /**
     * Receives events generated in the collections and dispatches them to an events-manager if available
     * Notify the behaviors that are listening in the collection
     *
     * @param string $eventName
     * @param CollectionInterface $collection
     * @return bool|null
     */
    public function notifyEvent(string $eventName, CollectionInterface $collection)
    {
        $status = null;

        if (is_array($this->behaviors) && isset($this->behaviors[strtolower(get_class($collection))])) {
            /**
             * Notify all the events on the behavior
             */
            foreach ($this->behaviors as $behavior) {
                if ($behavior->notify($eventName, $collection) === false) {
                    return false;
                }
            }
        }

        /**
         * Dispatch events to the global events manager
         */
        if (is_object($this->eventsManager)) {
            $status = $this->eventsManager->fire("collection:$eventName", $collection);

            if (!$status) {
                return $status;
            }
        }

        /**
         * A collection can has a specific events manager for it
         */
        if (is_array($this->customEventsManager)) {
            $customEventsManager = $this->customEventsManager[get_class_lower($collection)];

            if (isset($customEventsManager)) {
                $status = $customEventsManager->fire("collection:$eventName", $collection);

                if (!$status) {
                    return $status;
                }
            }
        }

        return $status;
    }

    /**
     * Dispatch an event to the listeners and behaviors
     * This method expects that the endpoint listeners/behaviors returns true
     * meaning that at least one was implemented
     *
     * @param CollectionInterface $collection
     * @param string $eventName
     * @param $data
     * @return bool
     */
    public function missingMethod(CollectionInterface $collection, string $eventName, $data): bool
    {
        /**
         * Dispatch events to the global events manager
         */
        if (is_array($this->behaviors) && isset($this->behaviors[get_class_lower($collection)])) {
            /**
             * Notify all the events on the behavior
             */
            foreach ($this->behaviors as $behavior) {
                $result = $behavior->missingMethod($collection, $eventName, $data);

                if ($result !== null) {
                    return $result;
                }
            }
        }

        /**
         * Dispatch events to the global events manager
         */
        if (is_object($this->eventsManager)) {
            return $this->eventsManager->fire("collection:$eventName", $collection, $data);
        }

        return false;
    }

    /**
     * Binds a behavior to a collection
     *
     * @param CollectionInterface $collection
     * @param BehaviorInterface $behavior
     */
    public function addBehavior(CollectionInterface $collection, BehaviorInterface $behavior)
    {
        $collectionsBehaviors = [];
        $entityName = get_class_lower($collection);

        /**
         * Get the current behaviors
         */
        if (isset($this->behaviors[$entityName])) {
            $collectionsBehaviors = $this->behaviors[$entityName];
        }

        /**
         * Append the behavior to the list of behaviors
         */
        $collectionsBehaviors[] = $behavior;

        /**
         * Update the behaviors list
         */
        $this->behaviors[$entityName] = $collectionsBehaviors;
    }

    /**
     * @return string
     */
    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    /**
     * @param string $serviceName
     */
    public function setServiceName(string $serviceName): void
    {
        $this->serviceName = $serviceName;
    }
}
