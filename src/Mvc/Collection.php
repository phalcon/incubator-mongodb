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

namespace Phalcon\Incubator\MongoDB\Mvc;

use ArrayIterator;
use JsonSerializable;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Serializable as BsonSerializable;
use MongoDB\BSON\Unserializable;
use MongoDB\Database;
use MongoDB\Driver\Cursor;
use Phalcon\Di\AbstractInjectionAware;
use Phalcon\Di\Di;
use Phalcon\Di\DiInterface;
use Phalcon\Events\ManagerInterface as EventsManagerInterface;
use Phalcon\Filter\Validation\ValidationInterface;
use Phalcon\Incubator\MongoDB\Mvc\Collection\BehaviorInterface;
use Phalcon\Incubator\MongoDB\Mvc\Collection\Exception;
use Phalcon\Incubator\MongoDB\Mvc\Collection\ManagerInterface;
use Phalcon\Messages\Message;
use Phalcon\Messages\MessageInterface;
use Phalcon\Mvc\EntityInterface;
use Phalcon\Support\HelperFactory;
use ReflectionClass;
use Serializable;
use Traversable;

/**
 * ActiveRecord class for the management of MongoDB collections
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
    //@codingStandardsIgnoreLine
    protected $_id;

    /**
     * @var Database|mixed
     */
    protected $connection;

    /**
     * @var DiInterface
     */
    protected $container;

    protected int $dirtyState = 1;

    protected static bool $disableEvents = false;

    protected static array $reserved = [];

    protected static array $typeMap = [];

    protected array $errorMessages = [];

    /**
     * @var ManagerInterface|null
     */
    protected $collectionsManager;

    protected int $operationMade = 0;

    protected bool $skipped = false;

    /**
     * @param array $data
     * @param DiInterface|null $container
     * @param ManagerInterface|null $collectionsManager
     * @throws Exception
     */
    final public function __construct(
        array $data = [],
        ?DiInterface $container = null,
        ?ManagerInterface $collectionsManager = null
    ) {
        if ($container === null) {
            $container = Di::getDefault();
        }

        if ($container === null) {
            throw new Exception('The services related to the ODM');
        }

        $this->container = $container;

        if ($collectionsManager === null) {
            $collectionsManager = $container->getShared('collectionsManager');

            if (!$collectionsManager instanceof ManagerInterface) {
                throw new Exception("The injected service 'collectionsManager' is not valid");
            }
        }

        $this->collectionsManager = $collectionsManager;
        $collectionsManager->initialize($this);

        if (method_exists($this, 'onConstruct')) {
            $this->onConstruct($data);
        }

        $this->assign($data);
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
     * @return CollectionInterface
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
     * @return Cursor|ArrayIterator
     * @throws Exception
     */
    public static function aggregate(array $parameters = [], array $options = []): Traversable
    {
        $className = static::class;

        /** @var CollectionInterface $base */
        $base = new $className();
        $source = $base->getSource();

        if (empty($source)) {
            throw new Exception("Method getSource() returns empty string");
        }

        /**
         * Check if a "typeMap" clause was defined or force default
         */
        if (isset($options["typeMap"])) {
            $options['typeMap'] = array_merge(self::getTypeMap('array'), $options["typeMap"]);
        } else {
            $options['typeMap'] = self::getTypeMap('array');
        }

        $connection = $base->getConnection();

        // Driver now return a Cursor class by default for more performances.
        return $connection->selectCollection($source)->aggregate($parameters, $options);
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

        /**
         * Mark the object as persistent
         */
        $collection->setDirtyState($dirtyState);

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
        $this->operationMade = self::OP_CREATE;
        $this->errorMessages = [];

        if ($this->preSave(self::$disableEvents, false) === false) {
            return false;
        }

        $success = false;
        $status = $collection->insertOne($this, [
            'w' => true,
        ]);

        if ($status->isAcknowledged()) {
            $success = true;
            $this->_id = $status->getInsertedId();
            $this->dirtyState = self::DIRTY_STATE_PERSISTENT;
        }

        return $this->postSave(
            self::$disableEvents,
            $success,
            false
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
        if (!$this->exists($collection)) {
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
        if ($this->preSave(self::$disableEvents, true) === false) {
            return false;
        }

        /**
         * We always use safe stores to get the success state
         * Save the document
         */
        $status = $collection->updateOne(
            ['_id' => $this->_id],
            ['$set' => $this->toArray()],
            ['w' => true],
        );

        /**
         * Call the postSave hooks
         */
        return $this->postSave(
            self::$disableEvents,
            $status->isAcknowledged(),
            true
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
        } elseif ($this->collectionsManager->isUsingImplicitObjectIds($this)) {
            $objectId = new ObjectId($this->_id);
        } else {
            $objectId = $this->_id;
        }

        $status = $collection->deleteOne(
            ['_id' => $objectId],
            ['w' => true],
        );

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
    public function writeAttribute(string $attribute, $value): void
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
            $eventName = !$exists ? 'afterCreate' : 'afterUpdate';

            $this->fireEvent($eventName);
            $this->fireEvent("afterSave");
        }

        return true;
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
        $className = static::class;
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
     * @return Cursor|Traversable
     * @throws Exception
     */
    public static function find(array $parameters = []): Traversable
    {
        $className = static::class;
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
        $className = static::class;
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
            if (!preg_match("/^[a-f\d]{24}$/i", (string)$id)) {
                return null;
            }

            $className = static::class;
            $collection = new $className();

            if ($collection->getCollectionsManager()->isUsingImplicitObjectIds($collection)) {
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
    public function getCollectionsManager(): ManagerInterface
    {
        return $this->collectionsManager;
    }

    /**
     * @return \MongoDB\Collection
     * @throws Exception
     */
    protected function prepareCU(): \MongoDB\Collection
    {
        if ($this->container === null) {
            throw new Exception('The services related to the ODM');
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
    public function bsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @param array $data
     * @throws Exception
     */
    public function bsonUnserialize(array $data): void
    {
        $container = Di::getDefault();

        if ($container === null) {
            throw new Exception('The services related to the ODM');
        }

        $this->container = $container;

        $collectionsManager = $container->getShared("collectionsManager");

        if ($collectionsManager === null) {
            throw new Exception("The injected service 'collectionsManager' is not valid");
        }

        $this->collectionsManager = $collectionsManager;

        $this->dirtyState = self::DIRTY_STATE_PERSISTENT;

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
        if (method_exists($this, $eventName) && $this->$eventName() === false) {
            return false;
        }

        /**
         * Send a notification to the events manager
         */
        return !($this->collectionsManager->notifyEvent($eventName, $this) === false);
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
        if ($container === null) {
            throw new Exception(
                "The dependency injector container is not valid"
            );
        }

        if ($container->has("serializer")) {
            $serializer = $this->container->getShared('serializer');
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
    public function setConnectionService(string $connectionService): self
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
    public function skipOperation(bool $skip): void
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
    protected function useImplicitObjectIds(bool $useImplicitObjectIds): void
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
            $base = static::class;
        } elseif ($base instanceof Unserializable) {
            $base = get_class($base);
        }

        $typeMap = [
            "root" => $base,
            "document" => 'array'
        ];

        /** @noinspection NotOptimalIfConditionsInspection */
        if (class_exists($base)) {
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

        $eventName = $this->operationMade === self::OP_DELETE
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
    public function unserialize($data): void
    {
        /**
         * Obtain the default DI
         */
        $container = Di::getDefault();
        if ($container === null) {
            throw new Exception("The services related to the ODM");
        }

        /**
         * Update the dependency injector
         */
        $this->container = $container;

        if ($container->has("serializer")) {
            $serializer = $container->getShared("serializer");
            $attributes = $serializer->unserialize($data);
        } else {
            /** @noinspection UnserializeExploitsInspection */
            $attributes = unserialize($data);
        }

        if (is_array($attributes)) {
            /**
             * Gets the default collectionsManager service
             */
            $manager = $container->getShared('collectionsManager');
            if ($manager === null) {
                throw new Exception(
                    "The injected service 'collectionsManager' is not valid"
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

        /**
         * Check if the model use implicit ids
         */
        if (!is_object($this->_id) && $this->collectionsManager->isUsingImplicitObjectIds($this)) {
            $this->_id = new ObjectId($this->_id);
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
     * @return Cursor|object|array
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
        if (isset($parameters["class"])) {
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
        } elseif (isset($parameters['conditions'])) {
            $conditions = $parameters['conditions'];
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

            return $document;
        }

        // Driver now return a Cursor class by default for more performances.
        return $mongoCollection->find($conditions, $parameters);
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
    protected static function getGroupResultset(
        array $parameters,
        CollectionInterface $collection,
        Database $connection
    ): int {
        $source = $collection->getSource();
        if (empty($source)) {
            throw new Exception("Method getSource() returns empty string");
        }

        $mongoCollection = $connection->selectCollection($source);

        $conditions = [];
        if (isset($parameters[0])) {
            $conditions = $parameters[0];
        } elseif (isset($parameters['conditions'])) {
            $conditions = $parameters['conditions'];
        }

        return $mongoCollection->countDocuments($conditions, $parameters);
    }

    final protected function possibleSetter(string $property, $value): bool
    {
        $possibleSetter = "set" . ucfirst((new HelperFactory())->camelize($property));

        if (!method_exists($this, $possibleSetter)) {
            return false;
        }

        $this->$possibleSetter($value);

        return true;
    }

    /**
     * @param string $property
     * @return mixed
     * @noinspection MissingReturnTypeInspection
     * @noinspection MethodVisibilityInspection
     */
    final protected function possibleGetter(string $property)
    {
        $possibleGetter = "get" . ucfirst((new HelperFactory())->camelize($property));

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

        // Use reflection to list uninitialized properties
        $reflection = new ReflectionClass($this);
        $reflectionProperties = $reflection->getProperties();
        $reserved = $this->getReservedAttributes();

        foreach ($reflectionProperties as $reflectionMethod) {
            $key = $reflectionMethod->getName();

            if (isset($reserved[$key])) {
                continue;
            }

            if (isset($dataMapped[$key])) {
                if (is_array($whiteList) && !in_array($key, $whiteList, true)) {
                    continue;
                }

                if (!$this->possibleSetter($key, $dataMapped[$key])) {
                    $this->$key = $dataMapped[$key];
                }
            }
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
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

    public function __serialize(): array
    {
        return $this->toArray();
    }

    public function __unserialize(array $data): void
    {
        $this->assign($data);
    }
}
