<?php

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

use Phalcon\Incubator\MongoDB\Mvc\CollectionInterface;

/**
 * Phalcon\Mvc\Collection\BehaviorInterface
 *
 * Interface for Phalcon\Mvc\Collection\Behavior
 */
interface BehaviorInterface
{
    /**
     * This method receives the notifications from the EventsManager
     *
     * @param string $type
     * @param CollectionInterface $collection
     * @return mixed
     */
    public function notify(string $type, CollectionInterface $collection);

    /**
     * Calls a method when it's missing in the collection
     *
     * @param CollectionInterface $collection
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function missingMethod(CollectionInterface $collection, string $method, array $arguments = []);
}
