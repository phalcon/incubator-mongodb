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

class FindByIdCest
{
    use DiTrait;

    private string $source;

    private Database $mongo;

    private ObjectId $tmpId;

    public function _before()
    {
        $this->setNewFactoryDefault();
        $this->setDiCollectionManager();
        $this->setDiMongo();

        $this->source = (new Robots())->getSource();
        $this->mongo = $this->getDi()->get('mongo');
        $this->tmpId = new ObjectId();

        $this->mongo->selectCollection($this->source)->insertMany(
            [
                [
                    'first_name' => 'Wall',
                    'last_name' => 'E',
                ],
                [
                    '_id' => $this->tmpId,
                    'first_name' => 'Unknown',
                    'last_name' => 'Nobody',
                ]
            ]
        );
    }

    /**
     * Tests Phalcon\Mvc\Collection :: findById()
     *
     * @param IntegrationTester $I
     * @throws Exception
     * @since  2018-11-13
     * @author Phalcon Team <team@phalcon.io>
     */
    public function mvcCollectionFindById(IntegrationTester $I)
    {
        $I->wantToTest('Mvc\Collection - findById()');

        $I->assertNull(Robots::findById(null));
        $I->assertNull(Robots::findById(new ObjectId()));
        $I->assertInstanceOf(Robots::class, Robots::findById($this->tmpId));
        $I->assertInstanceOf(Robots::class, Robots::findById((string)$this->tmpId));
    }

    public function _after()
    {
        $this->mongo->dropCollection($this->source);
    }
}
