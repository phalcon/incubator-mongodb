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

class SerializeUnserializeCest
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

        $this->mongo->selectCollection($this->source)->insertOne(
            [
                'first_name' => 'Wall',
                'last_name' => 'E',
            ]
        );
    }

    /**
     * Tests Phalcon\Mvc\Collection :: serialize()
     * Tests Phalcon\Mvc\Collection :: unserialize()
     *
     * @param IntegrationTester $I
     * @throws Exception
     * @since  2018-11-13
     * @author Phalcon Team <team@phalcon.io>
     */
    public function mvcCollectionSerialize(IntegrationTester $I)
    {
        $I->wantToTest('Mvc\Collection - serialize()');
        $I->wantToTest('Mvc\Collection - unserialize()');

        /** @var Robots $robot */
        $robot = Robots::findFirst();
        $serialized = $robot->serialize();

        $I->assertEquals($robot->toArray(), unserialize($serialized));
    }

    public function _after()
    {
        $this->mongo->dropCollection($this->source);
    }
}
