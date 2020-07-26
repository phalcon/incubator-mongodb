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

namespace Phalcon\Incubator\Test\Fixtures\Mvc\Collections\Documents;

use DateTime;
use DateTimeInterface;
use MongoDB\BSON\UTCDateTime;
use Phalcon\Incubator\Mvc\Collection\Document;

class RobotPart extends Document
{
    public $id;

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
        return $this->date
            ->toDateTime()
            ->format(DateTime::ISO8601);
    }
}
