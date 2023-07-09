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

namespace Phalcon\Incubator\MongoDB\Mvc\Collection\Behavior;

use Closure;
use Phalcon\Incubator\MongoDB\Mvc\Collection\Behavior;
use Phalcon\Incubator\MongoDB\Mvc\Collection\Exception;
use Phalcon\Incubator\MongoDB\Mvc\CollectionInterface;

/**
 * Allows to automatically update a collection’s attribute saving the
 * datetime when a record is created or updated
 */
class Timestampable extends Behavior
{
    /**
     * Listens for notifications from the collections manager
     *
     * @param string $type
     * @param CollectionInterface $collection
     * @return mixed|void|null
     * @throws Exception
     */
    public function notify(string $type, CollectionInterface $collection)
    {
        /**
         * Check if the developer decided to take action here
         */
        if ($this->mustTakeAction($type) !== true) {
            return null;
        }

        $options = $this->getOptions($type);
        if (!is_array($options)) {
            return;
        }

        /**
         * The field name is required in this behavior
         */
        if (!isset($options['field']) || !is_string($options['field'])) {
            throw new Exception("The option 'field' is required");
        }

        $timestamp = null;
        $field = $options['field'];
        if (isset($options['format'])) {
            /**
             * Format is a format for date()
             */
            $timestamp = date($options['format']);
        } else {
            if (isset($options['generator']) && $options['generator'] instanceof Closure) {
                $generator = $options['generator'];
                $timestamp = $generator();
            }
        }

        if ($timestamp === null) {
            $timestamp = time();
        }

        if (is_array($field)) {
            foreach ($field as $singleField) {
                $collection->writeAttribute($singleField, $timestamp);
            }
        } else {
            $collection->writeAttribute($field, $timestamp);
        }
    }
}
