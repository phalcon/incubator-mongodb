<?php

/**
 * This file is part of the Phalcon Framework.
 *
 * (c) Phalcon Team <team@phalconphp.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phalcon\Incubator\Mvc\Test\Integration\Collection;

use Codeception\TestInterface;
use IntegrationTester;
use MongoDB\Database;
use MongoDB\Driver\Cursor;
use Phalcon\Incubator\Test\Fixtures\Mvc\Collections\Robots;
use Phalcon\Incubator\Test\Fixtures\Traits\DiTrait;

/**
 * Class AggregateCest
 */
class AggregateCest
{
    use DiTrait;

    /** @var string $source */
    private $source;

    /** @var Database $mongo */
    private $mongo;

    public function _before()
    {
        $this->setNewFactoryDefault();
        $this->setDiCollectionManager();
        $this->setDiMongo();

        $this->source = (new Robots())->getSource();
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
                ]
            ]
        );
    }

    /**
     * Tests Phalcon\Mvc\Collection :: aggregate()
     *
     * @param IntegrationTester $I
     * @since  2018-11-13
     * @author Phalcon Team <team@phalconphp.com>
     */
    public function mvcCollectionAggregate(IntegrationTester $I)
    {
        $I->wantToTest('Mvc\Collection - aggregate()');

        $options = ['typeMap' => Robots::getTypeMap()];

        $robots1 = Robots::aggregate([
            [
                '$match' => [
                    'first_name' => 'Wall'
                ]
            ]
        ]);

        $robots2 = Robots::aggregate([
            [
                '$match' => [
                    'first_name' => 'Wall'
                ]
            ]
        ], $options);

        $I->assertNotEmpty($robots1);
        $I->assertInstanceOf(Robots::class, $robots2[0]);
    }

    public function _after()
    {
        $this->mongo->dropCollection($this->source);
    }
}
