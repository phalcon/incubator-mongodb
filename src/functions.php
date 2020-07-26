<?php

/** @noinspection PhpUndefinedClassInspection */

declare(strict_types=1);

/**
 * This file is part of the Phalcon Framework.
 *
 * (c) Phalcon Team <team@phalconphp.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

namespace Phalcon\Incubator;

use DateTime;
use Generator;
use MongoDB\BSON\ObjectIdInterface;
use MongoDB\BSON\UTCDateTimeInterface;
use ReflectionClass;
use ReflectionException;

/**
 * Return classname to lower
 *
 * @param object $object
 * @return string
 */
function get_class_lower(object $object): string
{
    return strtolower(get_class($object));
}

/**
 * Return class shortname
 *
 * @param object $object
 * @return string|null
 */
function get_class_ns(object $object): ?string
{
    try {
        $reflection = new ReflectionClass($object);
        return $reflection->getShortName();
    } catch (ReflectionException $e) {
        return null;
    }
}

/**
 * Serializer for MongoDB data to JSON format
 *
 * Cast ObjectId and UTCDateTime to string
 *
 * @param array $data
 * @param string $dateFormat By default use RFC3339
 * @return Generator
 */
function jsonSerializeGenerator(array $data = [], ?string $dateFormat = null): Generator
{
    if (is_null($dateFormat)) {
        $dateFormat = DateTime::RFC3339;
    }

    foreach ($data as $key => $value) {
        if (is_array($value)) {
            (yield $key => iterator_to_array(call_user_func(__FUNCTION__, $value, $dateFormat)));
            continue;
        } elseif (is_object($value)) {
            if ($value instanceof ObjectIdInterface) {
                (yield $key => (string)$value);
                continue;
            } elseif ($value instanceof UTCDateTimeInterface) {
                (yield $key => (string)$value->toDateTime()->format($dateFormat));
                continue;
            }
        }
        (yield $key => $value);
    }
}
