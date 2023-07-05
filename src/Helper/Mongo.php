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

namespace Phalcon\Incubator\MongoDB\Helper;

use DateTimeInterface;
use MongoDB\BSON\ObjectID;
use MongoDB\BSON\UTCDateTime;

class Mongo
{
    /**
     * Check if id parameter is a valid ObjectID
     *
     * @param mixed $id
     * @return bool
     * @noinspection MissingParameterTypeDeclarationInspection
     */
    final public static function isValidObjectId($id): bool
    {
        return $id instanceof ObjectID || preg_match('/^[a-f\d]{24}$/i', $id);
    }

    /**
     * Convert a DateTime object to UTCDateTime from MongoDB
     *
     * @param DateTimeInterface $dateTime
     * @return UTCDateTime
     */
    final public static function convertDatetime(DateTimeInterface $dateTime): UTCDateTime
    {
        return new UTCDateTime($dateTime->getTimestamp() * 1000);
    }
}
