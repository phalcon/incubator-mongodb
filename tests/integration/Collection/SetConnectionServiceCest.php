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
use MongoDB\Database;
use Phalcon\Incubator\MongoDB\Mvc\Collection\Exception;
use Phalcon\Incubator\MongoDB\Test\Fixtures\Mvc\Collections\Robots;
use Phalcon\Incubator\MongoDB\Test\Fixtures\Traits\DiTrait;

class SetConnectionServiceCest
{
    use DiTrait;

    private string $source;

    private Database $mongo;

    public function _before()
    {
        $this->setNewFactoryDefault();
        $this->setDiCollectionManager();
        $this->setDiMongo();

        $this->source = (new Robots())->getSource();
        $this->mongo = $this->getDi()->get('mongo');
        $this->getDi()->setShared('otherMongo', $this->mongo);
    }

    /**
     * Tests Phalcon\Mvc\Collection :: setConnectionService()
     *
     * @param IntegrationTester $I
     * @throws Exception
     * @since  2018-11-13
     * @author Phalcon Team <team@phalcon.io>
     */
    public function mvcCollectionSetConnectionService(IntegrationTester $I)
    {
        $I->wantToTest('Mvc\Collection - setConnectionService()');

        $robotOne = new Robots;
        $robotOne->first_name = 'One';

        $robotOne->setConnectionService('otherMongo');
        $I->assertEquals('otherMongo', $robotOne->getConnectionService());

        $I->assertTrue($robotOne->save());

        /** @var Robots $robot */
        $robot = Robots::findFirst();

        $I->assertNotFalse($robot);
        $I->assertInstanceOf(Robots::class, $robot);
        $I->assertEquals($robotOne->first_name, $robot->first_name);
    }

    public function _after()
    {
        $this->mongo->dropCollection($this->source);
        $this->mongo->dropCollection('otherMongo');
    }
}
