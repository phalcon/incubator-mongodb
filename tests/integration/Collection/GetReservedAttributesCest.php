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
use MongoDB\BSON\ObjectId;
use Phalcon\Incubator\Test\Fixtures\Mvc\Collections\Robots;
use Phalcon\Incubator\Test\Fixtures\Traits\DiTrait;

/**
 * Class GetReservedAttributesCest
 */
class GetReservedAttributesCest
{
    use DiTrait;

    public function _before()
    {
        $this->setNewFactoryDefault();
        $this->setDiCollectionManager();
        $this->setDiMongo();
    }

    /**
     * Tests Phalcon\Mvc\Collection :: getReservedAttributes()
     *
     * @param IntegrationTester $I
     * @since  2018-11-13
     * @author Phalcon Team <team@phalconphp.com>
     */
    public function mvcCollectionGetReservedAttributes(IntegrationTester $I)
    {
        $I->wantToTest('Mvc\Collection - getReservedAttributes()');

        $robot = new Robots();
        $robot->setId(new ObjectId());

        $allVars = $robot->revealObjectVars();
        $fields = $robot->toArray();
        $reservedDiff = array_diff_key($allVars, $fields);
        $reservedAttributes = array_fill_keys(array_keys($reservedDiff), true);
        unset($reservedAttributes['_id']);

        $I->assertEquals($reservedAttributes, $robot->getReservedAttributes());
    }
}
