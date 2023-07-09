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

namespace Phalcon\Incubator\MongoDB\Mvc\Test\Integration\Collection;

use IntegrationTester;
use MongoDB\BSON\ObjectId;
use MongoDB\Database;
use Phalcon\Incubator\MongoDB\Mvc\Collection\Exception;
use Phalcon\Incubator\MongoDB\Test\Fixtures\Mvc\Collections\Robots;
use Phalcon\Incubator\MongoDB\Test\Fixtures\Traits\DiTrait;

class SetIdCest
{
    use DiTrait;

    private string $source;

    private Database $mongo;

    public function _before()
    {
        $this->setNewFactoryDefault();
        $this->setDiCollectionsManager();
        $this->setDiMongo();

        $this->source = (new Robots())->getSource();
        $this->mongo = $this->getDi()->get('mongo');
    }

    /**
     * Tests Phalcon\Mvc\Collection :: setId()
     *
     * @param IntegrationTester $I
     * @throws Exception
     * @since  2018-11-13
     * @author Phalcon Team <team@phalcon.io>
     */
    public function mvcCollectionSetId(IntegrationTester $I)
    {
        $I->wantToTest('Mvc\Collection - setId()');

        $robot = new Robots();
        $robot->setId("5d07dc17a4881ea56c727b2f");
        $I->assertTrue($robot->save());

        $customRobot = $this->mongo->selectCollection($this->source)->findOne(
            [
                '_id' => new ObjectId("5d07dc17a4881ea56c727b2f")
            ]
        );

        $I->assertNotNull($customRobot);
    }

    public function _after()
    {
        $this->mongo->dropCollection($this->source);
    }
}
