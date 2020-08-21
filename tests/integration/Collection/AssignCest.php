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
use Phalcon\Incubator\MongoDB\Test\Fixtures\Mvc\Collections\Robots;
use Phalcon\Incubator\MongoDB\Test\Fixtures\Traits\DiTrait;

/**
 * Class AssignCest
 */
class AssignCest
{
    use DiTrait;

    public function _before()
    {
        $this->setNewFactoryDefault();
        $this->setDiCollectionManager();
        $this->setDiMongo();
    }

    /**
     * Tests Phalcon\Mvc\Collection :: __construct()
     *
     * @param IntegrationTester $I
     * @since  2018-11-13
     * @author Phalcon Team <team@phalcon.io>
     */
    public function mvcCollectionAssign(IntegrationTester $I): void
    {
        $I->wantToTest('Mvc\Collection - assign()');

        $name = 'Henry';

        $robot1 = new Robots();
        $robot1->assign([
            'first_name' => $name,
            'protected_field' => 71,
        ]);

        $I->assertEquals($robot1->first_name, $name);
        $I->assertEquals(72, $robot1->getProtectedField());

        $robot2 = new Robots();
        $robot2->assign([
            'firstn' => $name,
        ], [
            'firstn' => 'first_name'
        ]);

        $I->assertEquals($robot2->first_name, $name);

        $robot3 = new Robots();
        $robot3->assign([
            'first_name' => $name,
            'protected_field' => 0,
        ], null, [
            'first_name'
        ]);

        $I->assertEquals($robot3->first_name, $name);
        $I->assertEquals(42, $robot3->getProtectedField());
    }
}
