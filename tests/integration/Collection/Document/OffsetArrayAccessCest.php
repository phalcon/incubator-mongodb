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

class OffsetArrayAccessCest
{
    use DiTrait;

    public function _before()
    {
        $this->setNewFactoryDefault();
        $this->setDiCollectionsManager();
        $this->setDiMongo();
    }

    /**
     * Tests Phalcon\Mvc\Collection\Document :: offsetExists()
     * Tests Phalcon\Mvc\Collection\Document :: offsetGet()
     * Tests Phalcon\Mvc\Collection\Document :: offsetSet()
     *
     * @param IntegrationTester $I
     * @since  2018-11-13
     * @author Phalcon Team <team@phalcon.io>
     */
    public function mvcCollectionDocumentOffsetExists(IntegrationTester $I)
    {
        $I->wantToTest('Mvc\Collection\Document - offsetExists()');
        $I->wantToTest('Mvc\Collection\Document - offsetGet()');
        $I->wantToTest('Mvc\Collection\Document - offsetSet()');

        $robot = new Robots;
        $robot->setId(new ObjectId);
        $robot->first_name = 'Wall';
        $robot->last_name = 'E';

        $parts = [
            'id' => $robot->getId(),
            'common_name' => $robot->first_name . ' ' . $robot->last_name,
        ];

        $robotPart = new RobotPart($parts);
        $I->assertNotEmpty($robotPart->offsetExists('common_name'));
        $I->assertEmpty($robotPart->offsetExists('random'));

        $I->assertEquals($robot->getId(), $robotPart->offsetGet('id'));

        $robotPart->offsetSet('date', '2018-11-13');
        $I->assertEquals($robotPart->readAttribute('date'), '2018-11-13');
    }
}
