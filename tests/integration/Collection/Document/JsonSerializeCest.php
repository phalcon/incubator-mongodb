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

namespace Phalcon\Incubator\MongoDB\Mvc\Test\Integration\Collection\Document;

use IntegrationTester;
use MongoDB\BSON\ObjectId;
use Phalcon\Incubator\MongoDB\Test\Fixtures\Mvc\Collections\Documents\RobotPart;
use Phalcon\Incubator\MongoDB\Test\Fixtures\Traits\DiTrait;

class JsonSerializeCest
{
    use DiTrait;

    public function _before()
    {
        $this->setNewFactoryDefault();
        $this->setDiCollectionsManager();
        $this->setDiMongo();
    }

    /**
     * Tests Phalcon\Mvc\Collection\Document :: jsonSerialize()
     *
     * @param IntegrationTester $I
     */
    public function mvcCollectionDocumentJsonSerializeCest(IntegrationTester $I)
    {
        $I->wantToTest('Mvc\Collection\Document - jsonSerialize()');

        $parts = [
            'id' => new ObjectId(),
            'date' => null,
            'common_name' => 'Henry',
        ];

        $robotPart = new RobotPart($parts);
        $data = $robotPart->toArray();
        $data['id'] = (string)$data['id'];

        $dataCompare = json_decode(json_encode($data), true);
        $robotCompare = json_decode(json_encode($robotPart), true);

        $I->assertEquals($dataCompare, $robotCompare);
    }
}
