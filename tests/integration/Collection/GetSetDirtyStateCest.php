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
use Phalcon\Incubator\MongoDB\Mvc\Collection;
use Phalcon\Incubator\MongoDB\Mvc\Collection\Exception;
use Phalcon\Incubator\MongoDB\Test\Fixtures\Mvc\Collections\Robots;
use Phalcon\Incubator\MongoDB\Test\Fixtures\Traits\DiTrait;

class GetSetDirtyStateCest
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
     * Tests Phalcon\Mvc\Collection :: getDirtyState()
     * Tests Phalcon\Mvc\Collection :: setDirtyState()
     *
     * @param IntegrationTester $I
     * @throws Exception
     * @since  2018-11-13
     * @author Phalcon Team <team@phalcon.io>
     */
    public function mvcCollectionGetSetDirtyState(IntegrationTester $I)
    {
        $I->wantToTest('Mvc\Collection - getDirtyState()');
        $I->wantToTest('Mvc\Collection - setDirtyState()');

        $robot = new Robots();
        $I->assertEquals(Collection::DIRTY_STATE_TRANSIENT, $robot->getDirtyState());

        $robot->first_name = "wall";
        $I->assertTrue($robot->save());
        $I->assertEquals(Collection::DIRTY_STATE_PERSISTENT, $robot->getDirtyState());

        $I->assertTrue($robot->delete());
        $I->assertEquals(Collection::DIRTY_STATE_DETACHED, $robot->getDirtyState());

        $robot->setDirtyState(Collection::DIRTY_STATE_TRANSIENT);
        $I->assertEquals(Collection::DIRTY_STATE_TRANSIENT, $robot->getDirtyState());
    }

    public function _after()
    {
        $this->mongo->dropCollection($this->source);
    }
}
