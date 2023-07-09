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

use ArrayAccess;
use JsonSerializable;
use MongoDB\BSON\Serializable;
use MongoDB\BSON\Unserializable;
use Phalcon\Incubator\MongoDB\Mvc\CollectionInterface;
use Phalcon\Mvc\EntityInterface;
use Phalcon\Support\HelperFactory;
use ReflectionClass;

/**
 * This component allows Phalcon\Incubator\Mvc\Collection to return rows without an associated entity.
 * This objects implements the ArrayAccess interface to allow access the object as object->x or array[x].
 */
class Document implements
    EntityInterface,
    ArrayAccess,
    Unserializable,
    Serializable,
    JsonSerializable
{
    private HelperFactory $helperFactory;

    final public function __construct(array $data = [])
    {
        /**
         * This allows the developer to execute initialization stuff every time
         * an instance is created
         */
        if (method_exists($this, 'onConstruct')) {
            $this->onConstruct($data);
        }

        $this->assign($data);
    }

    /**
     * @param array $data
     * @param array $dataColumnMap
     * @param array $whiteList
     * @return $this|CollectionInterface
     */
    public function assign(array $data, array $dataColumnMap = [], array $whiteList = []): self
    {
        if (!empty($dataColumnMap)) {
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

        foreach ($reflectionProperties as $reflectionMethod) {
            $key = $reflectionMethod->getName();

            if (isset($dataMapped[$key])) {
                if (!in_array($key, $whiteList, true)) {
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
     * Checks whether an offset exists in the document
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return isset($this->$offset);
    }

    /**
     * Returns the value of a field using the ArrayAccess interface
     *
     * @param mixed $offset
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        return $this->$offset ?? null;
    }

    /**
     * Change a value using the ArrayAccess interface
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        $this->$offset = $value;
    }

    /**
     * Document cannot be changed. It has only been implemented to meet the definition of the ArrayAccess interface
     *
     * @param mixed $offset
     * @throws Exception
     */
    public function offsetUnset($offset): void
    {
        throw new Exception("The index does not exist in the document");
    }

    /**
     * Reads an attribute value by its name
     *
     * ```php
     * echo $robot->readAttribute("name");
     * ```
     *
     * @param string $attribute
     * @return mixed|null
     */
    public function readAttribute(string $attribute)
    {
        return $this->offsetGet($attribute);
    }

    /**
     * Writes an attribute value by its name
     *
     * ```php
     * $robot->writeAttribute("name", "Rosey");
     * ```
     *
     * @param string $attribute
     * @param mixed $value
     */
    public function writeAttribute(string $attribute, $value): void
    {
        $this->offsetSet($attribute, $value);
    }

    /**
     * Returns the instance as an array representation
     *
     * @return array
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /**
     * Serializes the object for json_encode
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        $data = [];

        foreach (get_object_vars($this) as $key => $value) {
            $data[$key] = $this->possibleGetter($key);
        }

        return $data;
    }

    /**
     * @param string $property
     * @return mixed
     */
    final protected function possibleGetter(string $property)
    {
        $possibleGetter = "get" . ucfirst($this->helperFactory->camelize($property));
        if (!method_exists($this, $possibleGetter)) {
            return $this->$property;
        }

        return $this->$possibleGetter();
    }

    /**
     * @return array
     */
    public function bsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @param string $property
     * @param $value
     * @return bool
     */
    final protected function possibleSetter(string $property, $value): bool
    {
        $possibleSetter = "set" . ucfirst($this->helperFactory->camelize($property));
        if (!method_exists($this, $possibleSetter)) {
            return false;
        }

        $this->$possibleSetter($value);

        return true;
    }

    /**
     * @param array $data
     */
    public function bsonUnserialize(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->offsetSet($key, $value);
        }
    }
}
