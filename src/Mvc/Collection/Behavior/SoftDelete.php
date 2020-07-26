<?php

declare(strict_types=1);

namespace Phalcon\Incubator\Mvc\Collection\Behavior;

use Phalcon\Incubator\Mvc\Collection\Behavior;
use Phalcon\Incubator\Mvc\Collection\Exception;
use Phalcon\Incubator\Mvc\CollectionInterface;
use Phalcon\Mvc\EntityInterface;

/**
 * Phalcon\Incubator\Mvc\Collection\Behavior\SoftDelete
 *
 * Instead of permanently delete a record it marks the record as
 * deleted changing the value of a flag column
 *
 * @package Phalcon\Incubator\Mvc\Collection\Behavior
 */
class SoftDelete extends Behavior
{
    /**
     * Listens for notifications from the collections manager
     *
     * @param string $type
     * @param CollectionInterface|EntityInterface $collection
     * @return mixed|void|null
     * @throws Exception
     */
    public function notify(string $type, CollectionInterface $collection)
    {
        if ($type === 'beforeDelete') {
            $options = $this->getOptions();

            $value = $options['value'];
            $field = $options['field'];

            /**
             * 'value' is the value to be updated instead of delete the record
             */
            if (!isset($value)) {
                throw new Exception("The option 'value' is required");
            }

            /**
             * 'field' is the attribute to be updated instead of delete the record
             */
            if (!is_string($field)) {
                throw new Exception("The option 'field' must be a string");
            }

            /**
             * Skip the current operation
             */
            $collection->skipOperation(true);

            /**
             * If the record is already flagged as 'deleted' we don't delete it again
             */
            if ($collection->readAttribute($field) !== $value) {
                /**
                 * Clone the current collection to make a clean new operation
                 */
                $updateCollection = clone $collection;

                $updateCollection->writeAttribute($field, $value);

                /**
                 * Update the cloned collection
                 */
                if (!$updateCollection->save()) {

                    /**
                     * Transfer the messages from the cloned collection to the original collection
                     */
                    foreach ($updateCollection->getMessages() as $message) {
                        $collection->appendMessage($message);
                    }

                    return false;
                }

                /**
                 * Update the original collection too
                 */
                $collection->writeAttribute($field, $value);
            }
        }
    }
}
