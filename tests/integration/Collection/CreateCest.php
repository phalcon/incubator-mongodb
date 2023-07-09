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

class CreateCest
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
    }

    /**
     * Tests Phalcon\Mvc\Collection :: create()
     *
     * @param IntegrationTester $I
     * @throws Exception
     * @since  2018-11-13
     * @author Phalcon Team <team@phalcon.io>
     */
    public function mvcCollectionCreate(IntegrationTester $I)
    {
        $I->wantToTest('Mvc\Collection - create()');

        $robot = new Robots();
        $robot->first_name = null;
        $I->assertTrue($robot->create());
        $I->assertEquals(Robots::DIRTY_STATE_PERSISTENT, $robot->getDirtyState());

        $search = $this->mongo->selectCollection($this->source)->findOne(['_id' => $robot->getId()]);
        $I->assertNotNull($search);
        $I->assertNotNull($search['_id']);
    }

    public function _after()
    {
        $this->mongo->dropCollection($this->source);
    }
}
