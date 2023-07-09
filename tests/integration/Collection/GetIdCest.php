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
use MongoDB\InsertOneResult;
use Phalcon\Incubator\MongoDB\Mvc\Collection\Exception;
use Phalcon\Incubator\MongoDB\Test\Fixtures\Mvc\Collections\Robots;
use Phalcon\Incubator\MongoDB\Test\Fixtures\Traits\DiTrait;

class GetIdCest
{
    use DiTrait;

    private string $source;

    private Database $mongo;

    /**
     * @var mixed
     */
    private $id;

    public function _before()
    {
        $this->setNewFactoryDefault();
        $this->setDiCollectionManager();
        $this->setDiMongo();

        $this->source = (new Robots)->getSource();
        $this->mongo = $this->getDi()->get('mongo');

        /** @var InsertOneResult $return */
        $return = $this->mongo->selectCollection($this->source)->insertOne(
            [
                'first_name' => 'Wall',
                'last_name' => 'E',
            ]
        );

        $this->id = $return->getInsertedId();
    }

    /**
     * Tests Phalcon\Mvc\Collection :: getId()
     *
     * @param IntegrationTester $I
     * @throws Exception
     * @since  2018-11-13
     * @author Phalcon Team <team@phalcon.io>
     */
    public function mvcCollectionGetId(IntegrationTester $I)
    {
        $I->wantToTest('Mvc\Collection - getId()');

        $robot = Robots::findFirst();

        $I->assertNotFalse($robot);
        $I->assertEquals($robot->getId(), $this->id);
    }

    public function _after()
    {
        $this->mongo->dropCollection($this->source);
    }
}
