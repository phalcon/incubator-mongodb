<?php

/** @noinspection PhpUndefinedClassInspection */

declare(strict_types=1);

/**
 * This file is part of the Phalcon Framework.
 *
 * (c) Phalcon Team <team@phalcon.io>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

namespace Phalcon\Incubator\MongoDB;

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
    $reflection = new ReflectionClass($object);

    return $reflection->getShortName();
}
