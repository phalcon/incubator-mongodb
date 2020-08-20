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

use MongoDB\BSON\ObjectId;
use MongoDB\Database;
use Phalcon\Messages\MessageInterface;
use Phalcon\Mvc\EntityInterface;

/**
 * Phalcon\Mvc\CollectionInterface
 *
 * Interface for Phalcon\Mvc\Collection
 */
interface CollectionInterface
{
    /**
     * Returns the value of the _id property
     *
     * @return mixed|ObjectId
     */
    public function getId();

    /**
     * Sets a value for the _id property, creates a MongoId object if needed
     *
     * @param mixed|ObjectId $id
     */
    public function setId($id): void;

    /**
     * Appends a customized message on the validation process
     *
     * @param MessageInterface $message
     * @return CollectionInterface
     */
    public function appendMessage(MessageInterface $message): CollectionInterface;

    /**
     * Returns a cloned collection
     *
     * @param CollectionInterface|EntityInterface $base
     * @param array $data
     * @param int $dirtyState
     * @return CollectionInterface
     */
    public static function cloneResult(
        CollectionInterface $base,
        array $data,
        int $dirtyState = 0
    ): CollectionInterface;

    /**
     * @param array $data
     * @param null $dataColumnMap
     * @param null $whiteList
     * @return CollectionInterface
     */
    public function assign(array $data, $dataColumnMap = null, $whiteList = null): CollectionInterface;

    /**
     * Perform a count over a collection
     *
     * @param array|null $parameters
     * @return int
     */
    public static function count(array $parameters = []): int;


    /**
     * Create a collection instance.
     *
     * Returning true on success or false otherwise
     *
     * @return bool
     */
    public function create(): bool;

    /**
     * Update a collection instance.
     *
     * Returning true on success or false otherwise
     *
     * @return bool
     */
    public function update(): bool;

    /**
     * Deletes a collection instance.
     *
     * Returning true on success or false otherwise
     *
     * @return bool
     */
    public function delete(): bool;

    /**
     * Creates/Updates a collection based on the values in the attributes
     *
     * @return bool
     */
    public function save(): bool;

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param array|null $parameters
     * @return iterable
     */
    public static function find(array $parameters = []): iterable;

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param array|null $parameters
     * @return CollectionInterface|null
     */
    public static function findFirst(array $parameters = []): ?CollectionInterface;

    /**
     * Find a document by its id
     *
     * @param mixed|ObjectId $id
     * @return CollectionInterface|null
     */
    public static function findById($id): ?CollectionInterface;

    /**
     * Fires an event
     *
     * Implicitly calls behaviors and listeners in the events manager are notified
     *
     * @param string $eventName
     * @return bool
     */
    public function fireEvent(string $eventName): bool;

    /**
     * Fires an event
     *
     * Implicitly listeners in the events manager are notified
     * This method stops if one of the callbacks/listeners returns bool false
     *
     * @param string $eventName
     * @return bool
     */
    public function fireEventCancel(string $eventName): bool;

    /**
     * Returns one of the DIRTY_STATE_* constants
     *
     * Telling if the record exists in the database or not
     *
     * @return int
     */
    public function getDirtyState(): int;

    /**
     * Returns all the validation messages
     *
     * @return MessageInterface[]
     */
    public function getMessages(): array;

    /**
     * Returns an array with reserved properties
     *
     * That cannot be part of the insert/update
     *
     * @return array
     */
    public function getReservedAttributes(): array;

    /**
     * Returns collection name mapped in the collection
     *
     * @return string
     */
    public function getSource(): string;

    /**
     * Sets a service in the services container that returns the MongoDB database
     *
     * @param string $connectionService
     */
    public function setConnectionService(string $connectionService);

    /**
     * Sets the dirty state of the object
     *
     * Using one of the DIRTY_STATE_* constants
     *
     * @param int $dirtyState
     * @return CollectionInterface
     */
    public function setDirtyState(int $dirtyState): CollectionInterface;

    /**
     * Retrieves a database connection
     *
     * @return mixed|Database
     */
    public function getConnection();
}
