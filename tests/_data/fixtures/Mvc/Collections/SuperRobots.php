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

namespace Phalcon\Incubator\MongoDB\Test\Fixtures\Mvc\Collections;

use Phalcon\Incubator\MongoDB\Mvc\Collection;
use Phalcon\Incubator\MongoDB\Test\Fixtures\Mvc\Collections\Documents\RobotPart;
use Phalcon\Messages\Message;
use Phalcon\Validation;
use Phalcon\Validation\Validator\Numericality;

/**
 * Class SuperRobots
 *
 * @package Phalcon\Incubator\MongoDB\Test\Fixtures\Mvc\Collections
 */
class SuperRobots extends Robots
{
    public function initialize(): void
    {
        self::$typeMap['fieldPaths']['rbpart2'] = RobotPart::class;
    }

    public $rbsuperversion = 1;

    public $rbpart2;
}
