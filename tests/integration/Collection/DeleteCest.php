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

class DeleteCest
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
                ]
            ]
        );
    }

    /**
     * Tests Phalcon\Mvc\Collection :: delete()
     *
     * @param IntegrationTester $I
     * @throws Exception
     * @since  2018-11-13
     * @author Phalcon Team <team@phalcon.io>
     */
    public function mvcCollectionDelete(IntegrationTester $I)
    {
        $I->wantToTest('Mvc\Collection - delete()');

        $robot = Robots::findFirst([['first_name' => 'Wall']]);

        $I->assertNotFalse($robot);
        $I->assertNotFalse($robot->delete());
    }

    public function _after()
    {
        $this->mongo->dropCollection($this->source);
    }
}
