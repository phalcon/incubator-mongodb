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

namespace Phalcon\Incubator\Mvc;

use JsonSerializable;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Serializable as BsonSerializable;
use MongoDB\BSON\Unserializable;
use MongoDB\Database;
use MongoDB\Driver\Cursor;
use Phalcon\Di;
use Phalcon\Di\AbstractInjectionAware;
use Phalcon\Di\DiInterface;
use Phalcon\Events\ManagerInterface as EventsManagerInterface;
use Phalcon\Helper\Str;
use Phalcon\Incubator\Mvc\Collection\BehaviorInterface;
use Phalcon\Incubator\Mvc\Collection\Exception;
use Phalcon\Incubator\Mvc\Collection\ManagerInterface;
use Phalcon\Messages\Message;
use Phalcon\Messages\MessageInterface;
use Phalcon\Mvc\EntityInterface;
use Phalcon\Validation\ValidationInterface;
use Serializable;

/**
 * Class Collection
 *
 * ActiveRecord class for the management of MongoDB collections
 *
 * @package Phalconator\Mvc
 */
class Collection extends AbstractInjectionAware implements
    CollectionInterface,
    EntityInterface,
    Serializable,
    BsonSerializable,
    Unserializable,
    JsonSerializable
{
    public const DIRTY_STATE_PERSISTENT = 0;
    public const DIRTY_STATE_TRANSIENT = 1;
    public const DIRTY_STATE_DETACHED = 2;

    public const OP_NONE = 0;
    public const OP_CREATE = 1;
    public const OP_UPDATE = 2;
    public const OP_DELETE = 3;

    /**
     * @var ObjectId|mixed $_id
     */
    protected $_id;

    /**
     * @var Database|mixed
     */
    protected $connection;

    /**
     * @var DiInterface|null
     */
    protected $container;

    protected $dirtyState = 1;

    protected static $disableEvents = false;

    /**
     * @var array $reserved
     */
    protected static $reserved;

    /**
     * @var array $typeMap
     */
    protected static $typeMap;

    protected $errorMessages = [];

    /**
     * @var ManagerInterface|null
     */
    protected $collectionsManager;

    protected $operationMade = 0;

    protected $skipped = false;

    /**
     * Collection constructor.
     *
     * @param null $data
     * @param DiInterface|null $container
     * @param ManagerInterface|null $collectionsManager
     * @throws Exception
     */
    final public function __construct(
        $data = null,
        ?DiInterface $container = null,
        ?ManagerInterface $collectionsManager = null
    ) {
        if (!is_object($container)) {
            $container = Di::getDefault();
        }

        if (!is_object($container)) {
            throw new Exception(Exception::containerServiceNotFound('the services related to the ODM'));
        }

        $this->container = $container;

        if (!is_object($collectionsManager)) {
            $collectionsManager = $container->getShared('collectionsManager');

            if (!is_object($collectionsManager)) {
                throw new Exception("The injected service 'collectionsManager' is not valid");
            }
        }

        $this->collectionsManager = $collectionsManager;

        $collectionsManager->initialize($this);

        if (method_exists($this, 'onConstruct')) {
            $this->onConstruct($data);
        }

        if (is_array($data)) {
            $this->assign($data);
        }
    }

    /**
     * @param BehaviorInterface $behavior
     */
    protected function addBehavior(BehaviorInterface $behavior): void
    {
        $this->collectionsManager->addBehavior($this, $behavior);
    }

    /**
     * @param MessageInterface $message
     * @return mixed|void
     */
    public function appendMessage(MessageInterface $message): CollectionInterface
    {
        $this->errorMessages[] = $message;

        return $this;
    }

    /**
     * Perform an aggregation using the Mongo aggregation framework
     *
     * @param array|null $parameters
     * @param array|null $options
     * @return array
     * @throws Exception
     */
    public static function aggregate(?array $parameters = [], ?array $options = []): array
    {
        $className = get_called_class();
        /** @var CollectionInterface $collection */
        $collection = new $className();

        $source = $collection->getSource();
        if (empty($source)) {
            throw new Exception("Method getSource() returns empty string");
        }

        $connection = $collection->getConnection();
        $cursorOrArrayIterator = $connection->selectCollection($source)->aggregate($parameters, $options);

        if ($cursorOrArrayIterator instanceof Cursor) {
            return $cursorOrArrayIterator->toArray();
        }

        return (array)$cursorOrArrayIterator;
    }

    /**
     * @param CollectionInterface|EntityInterface $base
     * @param array $data
     * @param int $dirtyState
     * @return CollectionInterface
     */
    public static function cloneResult(CollectionInterface $base, array $data, int $dirtyState = 0): CollectionInterface
    {
        $collection = clone $base;

        foreach ($data as $key => $value) {
            $collection->writeAttribute($key, $value);
        }

        if (method_exists($collection, 'afterFetch')) {
            $collection->afterFetch();
        }

        return $collection;
    }

    /**
     * Creates a collection based on the values in the attributes
     *
     * @return bool
     * @throws Exception
     */
    public function create(): bool
    {
        $collection = $this->prepareCU();

        $exists = false;
        $this->operationMade = self::OP_CREATE;
        $this->errorMessages = [];

        if ($this->preSave(self::$disableEvents, $exists) === false) {
            return false;
        }

        $success = false;
        $status = $collection->insertOne($this, [
            'w' => true
        ]);

        if ($status->isAcknowledged()) {
            $success = true;

            if ($exists === false) {
                $this->_id = $status->getInsertedId();
            }

            $this->dirtyState = self::DIRTY_STATE_PERSISTENT;
        }

        return $this->postSave(
            self::$disableEvents,
            $success,
            $exists
        );
    }

    /**
     * Creates/Updates a collection based on the values in the attributes
     *
     * @return bool
     * @throws Exception
     */
    public function update(): bool
    {
        $collection = $this->prepareCU();

        /**
         * Check the dirty state of the current operation to update the current
         * operation
         */
        $exists = $this->exists($collection);

        if (!$exists) {
            throw new Exception(
                "The document cannot be updated because it doesn't exist"
            );
        }

        $this->operationMade = self::OP_UPDATE;

        /**
         * The messages added to the validator are reset here
         */
        $this->errorMessages = [];

        /**
         * Execute the preSave hook
         */
        if ($this->preSave(self::$disableEvents, $exists) === false) {
            return false;
        }

        $data = $this->toArray();

        /**
         * We always use safe stores to get the success state
         * Save the document
         */
        $status = $collection->updateOne([
            '_id' => $this->_id
        ], [
            '$set' => $data
        ], [
            'w' => true
        ]);

        /**
         * Call the postSave hooks
         */
        return $this->postSave(
            self::$disableEvents,
            $status->isAcknowledged(),
            $exists
        );
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function delete(): bool
    {
        if (!isset($this->_id)) {
            throw new Exception(
                "The document cannot be deleted because it doesn't exist"
            );
        }

        $this->skipped = false;

        if (!self::$disableEvents && $this->fireEventCancel("beforeDelete") === false) {
            return false;
        }

        /**
         * Always return true if the operation is skipped
         */
        if ($this->skipped === true) {
            return true;
        }

        $source = $this->getSource();
        $connection = $this->getConnection();

        if (empty($source)) {
            throw new Exception("Method getSource() returns empty string");
        }

        $collection = $connection->selectCollection($source);

        if (is_object($this->_id)) {
            $objectId = $this->_id;
        } else {
            if ($this->collectionsManager->isUsingImplicitObjectIds($this)) {
                $objectId = new ObjectId($this->_id);
            } else {
                $objectId = $this->_id;
            }
        }

        $status = $collection->deleteOne([
            '_id' => $objectId
        ], [
            'w' => true
        ]);

        if ($status->getDeletedCount() === 0) {
            return false;
        }

        if ($status->isAcknowledged()) {
            $success = true;

            if (!self::$disableEvents) {
                $this->fireEvent("afterDelete");
            }

            $this->dirtyState = self::DIRTY_STATE_DETACHED;
        } else {
            $success = false;
        }

        return $success;
    }

    /**
     * @return mixed|Database
     */
    public function getConnection()
    {
        if (!is_object($this->connection)) {
            $this->connection = $this->collectionsManager->getConnection($this);
        }

        return $this->connection;
    }

    /**
     * @return string
     */
    public function getConnectionService(): string
    {
        return $this->collectionsManager->getConnectionService($this);
    }

    /**
     * @return DiInterface
     */
    public function getDI(): DiInterface
    {
        return $this->container;
    }

    /**
     * @return int
     */
    public function getDirtyState(): int
    {
        return $this->dirtyState;
    }

    /**
     * @return EventsManagerInterface
     */
    protected function getEventsManager(): EventsManagerInterface
    {
        return $this->collectionsManager->getCustomEventsManager($this);
    }

    /**
     * @return mixed|ObjectId
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param mixed|ObjectId $id
     */
    public function setId($id): void
    {
        if (!is_object($id)) {
            if ($this->collectionsManager->isUsingImplicitObjectIds($this)) {
                $this->_id = new ObjectId($id);
            } else {
                $this->_id = $id;
            }
        }
    }

    /**
     * @return MessageInterface[]
     */
    public function getMessages(): array
    {
        return $this->errorMessages;
    }

    /**
     * @return string
     */
    final public function getSource(): string
    {
        return $this->collectionsManager->getCollectionSource($this);
    }

    /**
     * @param string $attribute
     * @return mixed|null
     */
    public function readAttribute(string $attribute)
    {
        return $this->$attribute ?? null;
    }

    /**
     * Writes an attribute value by its name
     *
     *```php
     *    $robot->writeAttribute("name", "Rosey");
     *```
     *
     * @param string $attribute
     * @param mixed $value
     */
    public function writeAttribute(string $attribute, $value)
    {
        $this->$attribute = $value;
    }

    /**
     * Returns the instance as an array representation
     *
     *```php
     * print_r(
     *     $robot->toArray()
     * );
     *```
     *
     * @return array
     */
    public function toArray(): array
    {
        $reserved = $this->getReservedAttributes();

        /**
         * Get an array with the values of the object
         * We only assign values to the public properties
         */
        $data = [];

        foreach (get_object_vars($this) as $key => $value) {
            if ($key === '_id') {
                if ($value) {
                    $data[$key] = $value;
                }
            } elseif (!isset($reserved[$key])) {
                $data[$key] = $value;
            }
        }

        return $data;
    }

    /**
     * Executes internal hooks before save a document
     *
     * @param bool $disableEvents
     * @param bool $exists
     * @return bool
     */
    final protected function preSave(bool $disableEvents, bool $exists): bool
    {
        if (!$disableEvents) {
            if ($this->fireEventCancel('beforeValidation') === false) {
                return false;
            }

            $eventName = !$exists
                ? 'beforeValidationOnCreate'
                : 'beforeValidationOnUpdate';

            if ($this->fireEventCancel($eventName) === false) {
                return false;
            }
        }

        if ($this->fireEventCancel('validation') === false) {
            if (!$disableEvents) {
                $this->fireEvent('onValidationFails');
            }

            return false;
        }

        if (!$disableEvents) {
            $eventName = !$exists
                ? 'afterValidationOnCreate'
                : 'afterValidationOnUpdate';

            if ($this->fireEventCancel($eventName) === false) {
                return false;
            }

            if ($this->fireEventCancel('afterValidation') === false) {
                return false;
            }

            if ($this->fireEventCancel('beforeSave') === false) {
                return false;
            }

            $eventName = !$exists
                ? 'beforeCreate'
                : 'beforeUpdate';

            if ($this->fireEventCancel($eventName) === false) {
                return false;
            }

            if ($this->skipped === true) {
                return true;
            }
        }

        return true;
    }

    /**
     * @param bool $disableEvents
     * @param bool $success
     * @param bool $exists
     * @return bool
     */
    final protected function postSave(bool $disableEvents, bool $success, bool $exists): bool
    {
        if (!$success) {
            if (!$disableEvents) {
                $this->fireEvent('notSaved');
            }

            $this->cancelOperation($disableEvents);

            return false;
        }

        if (!$disableEvents) {
            $eventName = !$exists
                ? 'afterCreate'
                : 'afterUpdate';

            $this->fireEvent($eventName);
            $this->fireEvent("afterSave");
        }

        return $success;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function save(): bool
    {
        $collection = $this->prepareCU();

        $exists = $this->exists($collection);

        if ($exists === false) {
            $this->operationMade = self::OP_CREATE;
        } else {
            $this->operationMade = self::OP_UPDATE;
        }

        $this->errorMessages = [];

        if ($this->preSave(self::$disableEvents, $exists) === false) {
            return false;
        }

        $success = false;

        switch ($this->operationMade) {
            case self::OP_CREATE:
                $status = $collection->insertOne($this, [
                    'w' => true
                ]);
                break;

            case self::OP_UPDATE:
                $status = $collection->updateOne([
                    '_id' => $this->_id
                ], [
                    '$set' => $this
                ], [
                    'w' => true
                ]);
                break;

            default:
                throw new Exception("Invalid operation requested for " . __METHOD__);
        }

        if ($status->isAcknowledged()) {
            $success = true;

            if ($exists === false) {
                $this->_id = $status->getInsertedId();
                $this->dirtyState = self::DIRTY_STATE_PERSISTENT;
            }
        }

        return $this->postSave(
            self::$disableEvents,
            $success,
            $exists
        );
    }

    /**
     * Perform a count over a collection
     *
     * ```php
     * echo "There are ", Robots::count(), " robots";
     * ```
     *
     * @param null|array $parameters
     * @return int
     * @throws Exception
     */
    public static function count(array $parameters = []): int
    {
        $className = get_called_class();
        /** @var self $collection */
        $collection = new $className();
        $connection = $collection->getConnection();

        return self::getGroupResultset(
            $parameters,
            $collection,
            $connection
        );
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * ```php
     * // How many robots are there?
     * $robots = Robots::find();
     *
     * echo "There are ", count($robots), "\n";
     *
     * // How many mechanical robots are there?
     * $robots = Robots::find(
     *     [
     *         [
     *             "type" => "mechanical",
     *         ]
     *     ]
     * );
     *
     * echo "There are ", count(robots), "\n";
     *
     * // Get and print virtual robots ordered by name
     * $robots = Robots::findFirst(
     *     [
     *         [
     *             "type" => "virtual"
     *         ],
     *         "order" => [
     *             "name" => 1,
     *         ]
     *     ]
     * );
     *
     * foreach ($robots as $robot) {
     *       echo $robot->name, "\n";
     * }
     *
     * // Get first 100 virtual robots ordered by name
     * $robots = Robots::find(
     *     [
     *         [
     *             "type" => "virtual",
     *         ],
     *         "order" => [
     *             "name" => 1,
     *         ],
     *         "limit" => 100,
     *     ]
     * );
     *
     * foreach ($robots as $robot) {
     *       echo $robot->name, "\n";
     * }
     * ```
     *
     * @param array $parameters
     * @return iterable
     * @throws Exception
     */
    public static function find(array $parameters = []): iterable
    {
        $className = get_called_class();
        /** @var CollectionInterface $collection */
        $collection = new $className();

        return self::getResultset(
            $parameters,
            $collection,
            $collection->getConnection(),
            false
        );
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * ```php
     * // What's the first robot in the robots table?
     * $robot = Robots::findFirst();
     *
     * echo "The robot name is ", $robot->name, "\n";
     *
     * // What's the first mechanical robot in robots table?
     * $robot = Robots::findFirst(
     *     [
     *         [
     *             "type" => "mechanical",
     *         ]
     *     ]
     * );
     *
     * echo "The first mechanical robot name is ", $robot->name, "\n";
     *
     * // Get first virtual robot ordered by name
     * $robot = Robots::findFirst(
     *     [
     *         [
     *             "type" => "mechanical",
     *         ],
     *         "order" => [
     *             "name" => 1,
     *         ],
     *     ]
     * );
     *
     * echo "The first virtual robot name is ", $robot->name, "\n";
     *
     * // Get first robot by id (_id)
     * $robot = Robots::findFirst(
     *     [
     *         [
     *             "_id" => new \MongoDB\BSON\ObjectId("45cbc4a0e4123f6920000002"),
     *         ]
     *     ]
     * );
     *
     * echo "The robot id is ", $robot->_id, "\n";
     * ```
     *
     * @param array $parameters
     * @return CollectionInterface|null
     * @throws Exception
     */
    public static function findFirst(array $parameters = []): ?CollectionInterface
    {
        $className = get_called_class();
        /** @var CollectionInterface $collection */
        $collection = new $className();
        $connection = $collection->getConnection();

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return self::getResultset(
            $parameters,
            $collection,
            $connection,
            true
        );
    }

    /**
     * Find a document by its id (_id)
     *
     * <code>
     * // Find user by using \MongoDB\BSON\ObjectId object
     * $user = Users::findById(
     *     new \MongoDB\BSON\ObjectId("545eb081631d16153a293a66")
     * );
     *
     * // Find user by using id as sting
     * $user = Users::findById("45cbc4a0e4123f6920000002");
     *
     * // Validate input
     * if ($user = Users::findById($_POST["id"])) {
     *     // ...
     * }
     * ```
     *
     * @param mixed|ObjectId $id
     * @return CollectionInterface|null
     * @throws Exception
     */
    public static function findById($id): ?CollectionInterface
    {
        if (!is_object($id)) {
            if (!preg_match("/^[a-f\d]{24}$/i", $id)) {
                return null;
            }

            $className = get_called_class();
            $collection = new $className();

            if ($collection->getCollectionManager()->isUsingImplicitObjectIds($collection)) {
                $objectId = new ObjectId($id);
            } else {
                $objectId = $id;
            }
        } else {
            $objectId = $id;
        }

        return self::findFirst([
            'conditions' => [
                '_id' => $objectId
            ]
        ]);
    }

    /**
     * Returns the models manager related to the entity instance
     *
     * @return ManagerInterface
     */
    public function getCollectionManager(): ManagerInterface
    {
        return $this->collectionsManager;
    }

    /**
     * @return mixed|\MongoDB\Collection
     * @throws Exception
     */
    protected function prepareCU()
    {
        if (!is_object($this->container)) {
            throw new Exception(Exception::containerServiceNotFound('the services related to the ODM'));
        }

        $source = $this->getSource();

        if (empty($source)) {
            throw new Exception('Method getSource() returns empty string');
        }

        return $this
            ->getConnection()
            ->selectCollection($source);
    }

    /**
     * @return array
     */
    public function bsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * @param array $data
     * @throws Exception
     */
    public function bsonUnserialize(array $data)
    {
        $container = Di::getDefault();

        if (!is_object($container)) {
            throw new Exception(Exception::containerServiceNotFound('the services related to the ODM'));
        }

        $this->container = $container;

        $collectionsManager = $container->getShared("collectionsManager");

        if (!is_object($collectionsManager)) {
            throw new Exception("The injected service 'collectionsManager' is not valid");
        }

        $this->collectionsManager = $collectionsManager;

        foreach ($data as $key => $value) {
            $this->writeAttribute($key, $value);
        }

        if (method_exists($this, 'afterFetch')) {
            $this->afterFetch();
        }
    }

    /**
     * Fires an internal event
     *
     * @param string $eventName
     * @return bool
     */
    public function fireEvent(string $eventName): bool
    {
        /**
         * Check if there is a method with the same name of the event
         */
        if (method_exists($this, $eventName)) {
            $this->$eventName();
        }

        /**
         * Send a notification to the events manager
         */
        return (bool)$this->collectionsManager->notifyEvent($eventName, $this);
    }

    /**
     * Fires an internal event that cancels the operation
     *
     * @param string $eventName
     * @return bool
     */
    public function fireEventCancel(string $eventName): bool
    {
        /**
         * Check if there is a method with the same name of the event
         */
        if (method_exists($this, $eventName)) {
            if ($this->$eventName() === false) {
                return false;
            }
        }

        /**
         * Send a notification to the events manager
         */
        if ($this->collectionsManager->notifyEvent($eventName, $this) === false) {
            return false;
        }

        return true;
    }

    /**
     * Serializes the object ignoring connections or protected properties
     *
     * @return string
     * @throws Exception
     */
    public function serialize(): string
    {
        /**
         * Obtain the default DI
         */
        $container = Di::getDefault();
        if (!is_object($container)) {
            throw new Exception(
                "The dependency injector container is not valid"
            );
        }

        if ($container->has("serializer")) {
            $serializer = $this->container->getShared("serializer");

            $serializer->setData($this->toArray());

            return $serializer->serialize();
        }

        /**
         * Use the standard serialize function to serialize the array data
         */
        return serialize($this->toArray());
    }

    /**
     * Sets the DependencyInjection connection service name
     *
     * @param string $connectionService
     * @return $this
     */
    public function setConnectionService($connectionService)
    {
        $this->collectionsManager->setConnectionService($this, $connectionService);

        return $this;
    }

    /**
     * Sets the dependency injection container
     *
     * @param DiInterface $container
     */
    public function setDI(DiInterface $container): void
    {
        $this->container = $container;
    }

    /**
     * Sets collection name which model should be mapped
     *
     * @param string $source
     * @return $this
     */
    final protected function setSource(string $source): CollectionInterface
    {
        $this->collectionsManager->setCollectionSource($this, $source);

        return $this;
    }

    /**
     * Sets the dirty state of the object using one of the DIRTY_STATE_* constants
     *
     * @param int $dirtyState
     * @return $this|CollectionInterface
     */
    public function setDirtyState(int $dirtyState): CollectionInterface
    {
        $this->dirtyState = $dirtyState;

        return $this;
    }

    /**
     * Skips the current operation forcing a success state
     *
     * @param bool $skip
     */
    public function skipOperation(bool $skip)
    {
        $this->skipped = $skip;
    }

    /**
     * Executes validators on every validation call
     *
     *```php
     * use Phalcon\Incubator\Mvc\Collection;
     * use Phalcon\Validation;
     * use Phalcon\Validation\Validator\ExclusionIn;
     *
     * class Subscriptors extends Collection
     * {
     *     public function validation()
     *     {
     *         $validator = new Validation();
     *
     *         $validator->add(
     *             "status",
     *             new ExclusionIn(
     *                 [
     *                     "domain" => [
     *                         "A",
     *                         "I",
     *                     ],
     *                 ]
     *             )
     *         );
     *
     *         return $this->validate($validator);
     *     }
     * }
     *```
     *
     * @param ValidationInterface $validator
     * @return bool
     */
    protected function validate(ValidationInterface $validator): bool
    {
        $messages = $validator->validate(null, $this);

        // Call the validation, if it returns not the bool
        // we append the messages to the current object
        if (is_bool($messages)) {
            return $messages;
        }

        /** @var MessageInterface $message */
        foreach (iterator_to_array($messages) as $message) {
            $this->appendMessage(
                new Message(
                    $message->getMessage(),
                    $message->getField(),
                    $message->getType(),
                    $message->getCode()
                )
            );
        }

        // If there is a message, it returns false otherwise true
        return !count($messages);
    }

    /**
     * Sets if a collection must use implicit objects ids
     *
     * @param bool $useImplicitObjectIds
     */
    protected function useImplicitObjectIds(bool $useImplicitObjectIds)
    {
        $this->collectionsManager->useImplicitObjectIds($this, $useImplicitObjectIds);
    }

    /**
     * Returns an array with reserved properties that cannot be part of the insert/update
     *
     * @return array
     */
    public function getReservedAttributes(): array
    {
        $reserved = [
            "connection" => true,
            "container" => true,
            "operationMade" => true,
            "errorMessages" => true,
            "dirtyState" => true,
            "collectionsManager" => true,
            "skipped" => true
        ];

        if (is_array(self::$reserved)) {
            $reserved = array_merge($reserved, self::$reserved);
        }

        return $reserved;
    }

    /**
     * Return typeMap MongoDB array
     *
     * @param mixed $base
     * @return array
     */
    public static function getTypeMap($base = null): array
    {
        if (is_null($base)) {
            $base = get_called_class();
        }

        $typeMap = [
            "root" => is_object($base)
                ? get_class($base)
                : (string)$base,
            "document" => 'array'
        ];

        if (is_array($base::$typeMap)) {
            $typeMap = array_merge($typeMap, $base::$typeMap);
        }

        return $typeMap;
    }

    /**
     * Cancel the current operation
     *
     * @param bool $disableEvents
     * @return bool
     */
    protected function cancelOperation(bool $disableEvents): bool
    {
        if ($disableEvents) {
            return false;
        }

        $eventName = $this->operationMade == self::OP_DELETE
            ? 'notDeleted'
            : 'notSaved';

        $this->fireEvent($eventName);

        return true;
    }

    /**
     * Unserializes the object from a serialized string
     *
     * @param string $data
     * @throws Exception
     */
    public function unserialize($data)
    {
        /**
         * Obtain the default DI
         */
        $container = Di::getDefault();
        if (!is_object($container)) {
            throw new Exception(
                Exception::containerServiceNotFound(
                    "the services related to the ODM"
                )
            );
        }

        /**
         * Update the dependency injector
         */
        $this->container = $container;

        if ($container->has("serializer")) {
            $serializer = $container->getShared("serializer");
            $attributes = $serializer->unserialize($data);
        } else {
            $attributes = unserialize($data);
        }

        if (is_array($attributes)) {
            /**
             * Gets the default collectionsManager service
             */
            $manager = $container->getShared("collectionManager");

            if (!is_object($manager)) {
                throw new Exception(
                    "The injected service 'collectionManager' is not valid"
                );
            }

            /**
             * Update the collections manager
             */
            $this->collectionsManager = $manager;

            /**
             * Update the objects attributes
             */
            foreach ($attributes as $key => $value) {
                $this->writeAttribute($key, $value);
            }
        }
    }

    /**
     * Checks if the document exists in the collection
     *
     * @param $collection
     * @return bool
     */
    protected function exists($collection): bool
    {
        if (!isset($this->_id)) {
            return false;
        }

        if (!is_object($this->_id)) {
            /**
             * Check if the model use implicit ids
             */
            if ($this->collectionsManager->isUsingImplicitObjectIds($this)) {
                $this->_id = new ObjectId($this->_id);
            }
        }

        /**
         * If we already know if the document exists we don't check it
         */
        if (!$this->dirtyState) {
            return true;
        }

        /**
         * Perform the count using the function provided by the driver
         */
        $exists = $collection->count(['_id' => $this->_id]) > 0;

        $this->dirtyState = $exists
            ? self::DIRTY_STATE_PERSISTENT
            : self::DIRTY_STATE_TRANSIENT;

        return $exists;
    }

    /**
     * Returns a collection resultset
     *
     * @param array $parameters
     * @param CollectionInterface $collection
     * @param mixed|Database $connection
     * @param bool $unique
     * @return array|object|null
     * @throws Exception
     */
    protected static function getResultset(
        array $parameters,
        CollectionInterface $collection,
        $connection,
        bool $unique
    ) {
        /**
         * Check if "class" clause was defined
         */
        if (isset($className)) {
            $className = $parameters["class"];
            $base = new $className();

            if (!($base instanceof CollectionInterface || $base instanceof Collection\Document)) {
                throw new Exception(
                    "Object of class $className must be an implementation of"
                    . "Phalcon\\Mvc\\CollectionInterface or an instance of Phalcon\\Mvc\\Collection\\Document"
                );
            }
        } else {
            $base = $collection;
        }

        if ($base instanceof Collection) {
            $base->setDirtyState(self::DIRTY_STATE_PERSISTENT);
        }

        $source = $collection->getSource();
        if (empty($source)) {
            throw new Exception("Method getSource() returns empty string");
        }

        $mongoCollection = $connection->selectCollection($source);

        if (!is_object($mongoCollection)) {
            throw new Exception("Couldn't select mongo collection");
        }

        $conditions = [];
        if (isset($parameters[0])) {
            $conditions = $parameters[0];
        } else {
            if (isset($parameters['conditions'])) {
                $conditions = $parameters['conditions'];
            }
        }

        if (!is_array($conditions)) {
            throw new Exception("Find parameters must be an array");
        }

        /**
         * Check if a "typeMap" clause was defined or force default
         */
        if (isset($parameters["typeMap"])) {
            $parameters['typeMap'] = array_merge(
                self::getTypeMap($base),
                $parameters["typeMap"]
            );
        } else {
            $parameters['typeMap'] = self::getTypeMap($base);
        }

        if ($unique) {
            /**
             * Requesting a single result
             */
            $document = $mongoCollection->findOne($conditions, $parameters);

            if (empty($document)) {
                return null;
            }

            if (method_exists($base, 'afterFetch')) {
                $base->afterFetch();
            }

            return $document;
        }

        /**
         * Requesting a complete resultset
         */
        $documentsCursor = $mongoCollection->find($conditions, $parameters);

        if (method_exists($base, 'afterFetch')) {
            $base->afterFetch();
        }

        return $documentsCursor->toArray();
    }

    /**
     * Perform a count over a resultset
     *
     * @param array $parameters
     * @param CollectionInterface $collection
     * @param Database $connection
     * @return int
     * @throws Exception
     */
    protected static function getGroupResultset(array $parameters, CollectionInterface $collection, $connection): int
    {
        $source = $collection->getSource();
        if (empty($source)) {
            throw new Exception("Method getSource() returns empty string");
        }

        $mongoCollection = $connection->selectCollection($source);

        $conditions = [];
        if (isset($parameters[0])) {
            $conditions = $parameters[0];
        } else {
            if (isset($parameters['conditions'])) {
                $conditions = $parameters['conditions'];
            }
        }

        return $mongoCollection->countDocuments($conditions, $parameters);
    }

    final protected function possibleSetter(string $property, $value): bool
    {
        $possibleSetter = "set" . Str::camelize($property);

        if (!method_exists($this, $possibleSetter)) {
            return false;
        }

        $this->$possibleSetter($value);

        return true;
    }

    final protected function possibleGetter(string $property)
    {
        $possibleGetter = "get" . Str::camelize($property);

        if (!method_exists($this, $possibleGetter)) {
            return $this->$property;
        }

        return $this->$possibleGetter();
    }

    /**
     * @param array $data
     * @param null $dataColumnMap
     * @param null $whiteList
     * @return $this|CollectionInterface
     */
    public function assign(array $data, $dataColumnMap = null, $whiteList = null): CollectionInterface
    {
        if (is_array($dataColumnMap)) {
            $dataMapped = [];

            foreach ($data as $key => $value) {
                if (isset($dataColumnMap[$key])) {
                    $dataMapped[$dataColumnMap[$key]] = $value;
                }
            }
        } else {
            $dataMapped = $data;
        }

        if (count($dataMapped) === 0) {
            return $this;
        }

        foreach (get_object_vars($this) as $key => $value) {
            $reserved = $this->getReservedAttributes();

            if (isset($dataMapped[$key])) {
                if (is_array($whiteList) && !in_array($key, $whiteList)) {
                    continue;
                }

                if (!$this->possibleSetter($key, $value)) {
                    $this->$key = $value;
                }
            }
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        $data = [];
        $reserved = $this->getReservedAttributes();

        foreach (get_object_vars($this) as $key => $value) {
            if ($key === '_id') {
                if ($value) {
                    $data[$key] = (string)$value;
                }
            } elseif (!isset($reserved[$key])) {
                $data[$key] = $this->possibleGetter($key);
            }
        }

        return $data;
    }
}
