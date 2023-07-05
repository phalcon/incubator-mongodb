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

namespace Phalcon\Incubator\MongoDB\Test\Fixtures\Mvc\Collections\Documents;

use DateTime;
use DateTimeInterface;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use Phalcon\Incubator\MongoDB\Helper\Mongo;
use Phalcon\Incubator\MongoDB\Mvc\Collection\Document;

class RobotPart extends Document
{
    protected $id;

    public $common_name;

    /**
     * @var UTCDateTime $date
     */
    protected $date;

    /**
     * @param mixed $date
     * @return RobotPart
     */
    public function setDate(DateTimeInterface $date)
    {
        $this->date = new UTCDateTime($date->getTimestamp() * 1000);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDate()
    {
        if (null !== $this->date) {
            return $this->date
                ->toDateTime()
                ->format(DateTime::ATOM);
        }

        return null;
    }

    /**
     * @param string $type
     * @return string|null
     */
    public function getId(string $type = 'string'): ?string
    {
        switch ($type) {
            case 'string':
                return (string) $this->id;

            case 'object':
                return $this->id;

            default:
                return null;
        }
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = Mongo::isValidObjectId($id)
            ? new ObjectId((string)$id)
            : null;
    }
}
