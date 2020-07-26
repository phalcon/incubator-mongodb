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

use IntegrationTester;
use MongoDB\Database;
use Phalcon\Incubator\Test\Fixtures\Mvc\Collections\Robots;
use Phalcon\Incubator\Test\Fixtures\Traits\DiTrait;

/**
 * Class ToArrayCest
 */
class ToArrayCest
{
    use DiTrait;

    /** @var string $source */
    private $source;

    /** @var Database $mongo */
    private $mongo;

    /** @var array $data */
    private $data;

    public function _before()
    {
        $this->setNewFactoryDefault();
        $this->setDiCollectionManager();
        $this->setDiMongo();

        $this->source = (new Robots())->getSource();
        $this->mongo = $this->getDi()->get('mongo');

        $this->data = [
            'first_name' => 'Unknown',
            'last_name' => 'Nobody',
            'sub' => [
                'bool' => false,
                'float' => 0.02
            ],
            'version' => 1,
            'rbpart' => null,
            'protected_field' => 10
        ];

        $insertOneResult = $this->mongo->selectCollection($this->source)->insertOne($this->data);
        $this->data['_id'] = $insertOneResult->getInsertedId();
    }

    /**
     * Tests Phalcon\Mvc\Collection :: toArray()
     *
     * @param IntegrationTester $I
     * @since  2018-11-13
     * @author Phalcon Team <team@phalconphp.com>
     */
    public function mvcCollectionToArray(IntegrationTester $I)
    {
        $I->wantToTest('Mvc\Collection - toArray()');

        /** @var Robots $robot */
        $robot = Robots::findFirst();
        $data = $this->data;
        $data['_id'] = (string)$data['_id'];

        $I->assertEquals($robot->toArray(), $data);
    }

    public function _after()
    {
        $this->mongo->dropCollection($this->source);
    }
}
