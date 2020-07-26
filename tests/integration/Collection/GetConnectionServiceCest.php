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
use Phalcon\Incubator\Test\Fixtures\Mvc\Collections\Robots;
use Phalcon\Incubator\Test\Fixtures\Traits\DiTrait;

/**
 * Class GetConnectionServiceCest
 */
class GetConnectionServiceCest
{
    use DiTrait;

    public function _before()
    {
        $this->setNewFactoryDefault();
        $this->setDiCollectionManager();
        $this->setDiMongo();

        $mongo = $this->getDi()->get('mongo');
        $this->getDi()->set('otherMongo', $mongo);
    }

    /**
     * Tests Phalcon\Mvc\Collection :: getConnectionService()
     *
     * @param IntegrationTester $I
     * @since  2018-11-13
     * @author Phalcon Team <team@phalconphp.com>
     */
    public function mvcCollectionGetConnectionService(IntegrationTester $I)
    {
        $I->wantToTest('Mvc\Collection - getConnectionService()');

        $robot = new Robots;
        $I->assertEquals("mongo", $robot->getConnectionService());

        $connectionService = 'otherMongo';
        $robot->setConnectionService($connectionService);
        $I->assertEquals($connectionService, $robot->getConnectionService());
    }
}
