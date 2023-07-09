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
use Phalcon\Incubator\MongoDB\Test\Fixtures\Traits\DiTrait;

class AssignCest
{
    use DiTrait;

    public function _before()
    {
        $this->setNewFactoryDefault();
        $this->setDiCollectionManager();
        $this->setDiMongo();
    }

    /**
     * Tests Phalcon\Mvc\Collection\Document :: assign()
     *
     * @param IntegrationTester $I
     */
    public function mvcCollectionDocumentConstruct(IntegrationTester $I)
    {
        $I->wantToTest('Mvc\Collection\Document - assign()');

        $robotPart = new RobotPart();
        $robotPart->assign([
            'id' => new ObjectId(),
        ]);

        $I->assertInstanceOf(ObjectId::class, $robotPart->getId('object'));
    }
}
