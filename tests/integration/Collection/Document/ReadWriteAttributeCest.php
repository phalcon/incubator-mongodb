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

namespace Phalcon\Incubator\MongoDB\Mvc\Test\Integration\Collection\Document;

use IntegrationTester;
use MongoDB\BSON\ObjectId;
use Phalcon\Incubator\MongoDB\Test\Fixtures\Mvc\Collections\Documents\RobotPart;
use Phalcon\Incubator\MongoDB\Test\Fixtures\Mvc\Collections\Robots;
use Phalcon\Incubator\MongoDB\Test\Fixtures\Traits\DiTrait;

class ReadWriteAttributeCest
{
    use DiTrait;

    public function _before()
    {
        $this->setNewFactoryDefault();
        $this->setDiCollectionsManager();
        $this->setDiMongo();
    }

    /**
     * Tests Phalcon\Mvc\Collection\Document :: readAttribute()
     * Tests Phalcon\Mvc\Collection\Document :: writeAttribute()
     *
     * @param IntegrationTester $I
     * @since  2018-11-13
     * @author Phalcon Team <team@phalcon.io>
     */
    public function mvcCollectionDocumentReadWriteAttribute(IntegrationTester $I)
    {
        $I->wantToTest('Mvc\Collection\Document - readAttribute()');
        $I->wantToTest('Mvc\Collection\Document - writeAttribute()');

        $robot = new Robots;
        $robot->setId(new ObjectId);
        $robot->first_name = 'Wall';
        $robot->last_name = 'E';

        $common_name = $robot->first_name . ' ' . $robot->last_name;

        $robotPart = new RobotPart(['common_name' => $common_name]);

        $robotPart->writeAttribute('id', $robot->getId());
        $I->assertEquals($robotPart->getId('object'), $robot->getId());

        $partName = $robotPart->readAttribute('common_name');
        $I->assertEquals($common_name, $partName);
    }
}
