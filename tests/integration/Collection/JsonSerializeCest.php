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

use DateTime;
use IntegrationTester;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Database;
use Phalcon\Incubator\MongoDB\Mvc\Collection\Exception;
use Phalcon\Incubator\MongoDB\Test\Fixtures\Mvc\Collections\Documents\RobotPart;
use Phalcon\Incubator\MongoDB\Test\Fixtures\Mvc\Collections\Robots;
use Phalcon\Incubator\MongoDB\Test\Fixtures\Traits\DiTrait;

class JsonSerializeCest
{
    use DiTrait;

    private string $source;

    private Database $mongo;

    private array $data;

    public function _before()
    {
        $this->setNewFactoryDefault();
        $this->setDiCollectionsManager();
        $this->setDiMongo();

        $this->source = (new Robots())->getSource();
        $this->mongo = $this->getDi()->get('mongo');

        $rbpart = new RobotPart();
        $rbpart->setId(new ObjectId());
        $rbpart->setDate(new DateTime());

        $this->data = [
            '_id' => new ObjectId(),
            'first_name' => 'Unknown',
            'last_name' => 'Nobody',
            'sub' => [
                'bool' => false,
                'float' => 0.02,
            ],
            'sub2' => [
                'id' => new ObjectId(),
                'date' => new UTCDateTime(1595061104 * 1000),
            ],
            'rbpart' => $rbpart,
            'version' => 1,
            'protected_field' => 10,
        ];

        $insertOneResult = $this->mongo->selectCollection($this->source)->insertOne($this->data);
        $this->data['_id'] = $insertOneResult->getInsertedId();
    }

    /**
     * Tests Phalcon\Mvc\Collection :: toArray()
     *
     * @param IntegrationTester $I
     * @throws Exception
     * @since  2018-11-13
     * @author Phalcon Team <team@phalcon.io>
     */
    public function mvcCollectionJsonSerialize(IntegrationTester $I)
    {
        $I->wantToTest('Mvc\Collection - jsonSerialize()');

        /** @var Robots $robot */
        $robot = Robots::findById($this->data['_id']);
        $data = $this->data;
        $data['_id'] = (string)$data['_id'];

        $dataCompare = json_decode(json_encode($data), true);
        $robotCompare = json_decode(json_encode($robot), true);

        $I->assertEquals($dataCompare, $robotCompare);
    }

    public function _after()
    {
        $this->mongo->dropCollection($this->source);
    }
}
