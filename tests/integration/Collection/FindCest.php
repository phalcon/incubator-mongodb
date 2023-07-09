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
use Traversable;

class FindCest
{
    use DiTrait;

    private string $source;

    private Database $mongo;

    public function _before()
    {
        $this->setNewFactoryDefault();
        $this->setDiCollectionsManager();
        $this->setDiMongo();

        $this->source = (new Robots)->getSource();
        $this->mongo = $this->getDi()->get('mongo');

        $this->mongo->selectCollection($this->source)->insertMany(
            [
                [
                    'first_name' => 'Wall',
                    'last_name' => 'E',
                ],
                [
                    'first_name' => 'Unknown',
                    'last_name' => 'Nobody',
                ],
                [
                    'first_name' => 'Termin',
                    'last_name' => 'E',
                ]
            ]
        );
    }

    /**
     * Tests Phalcon\Mvc\Collection :: find()
     *
     * @param IntegrationTester $I
     * @throws Exception
     * @since  2018-11-13
     * @author Phalcon Team <team@phalcon.io>
     */
    public function mvcCollectionFind(IntegrationTester $I)
    {
        $I->wantToTest('Mvc\Collection - find()');

        $robots = Robots::find();
        $robots1 = Robots::find();
        $robotsE = Robots::find([['last_name' => 'E']]);

        $result = $robots->toArray();

        foreach ($robots1 as $rb) {
            $I->assertInstanceOf(Robots::class, $rb);
        }

        $I->assertNotEmpty($robots);
        $I->assertInstanceOf(Traversable::class, $robots);
        $I->assertInstanceOf(Robots::class, $result[0]);
        $I->assertInstanceOf(Traversable::class, $robotsE);
        $I->assertCount(3, $result);
        $I->assertCount(2, $robotsE->toArray());
    }

    public function _after()
    {
        $this->mongo->dropCollection($this->source);
    }
}
