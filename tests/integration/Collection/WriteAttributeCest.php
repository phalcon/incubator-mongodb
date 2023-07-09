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

class WriteAttributeCest
{
    use DiTrait;

    public function _before()
    {
        $this->setNewFactoryDefault();
        $this->setDiCollectionsManager();
        $this->setDiMongo();
    }

    /**
     * Tests Phalcon\Mvc\Collection :: writeAttribute()
     *
     * @param IntegrationTester $I
     * @since  2018-11-13
     * @author Phalcon Team <team@phalcon.io>
     */
    public function mvcCollectionWriteAttribute(IntegrationTester $I)
    {
        $I->wantToTest('Mvc\Collection - writeAttribute()');

        $firstName = 'Hola';
        $robot = new Robots();
        $robot->writeAttribute('first_name', $firstName);

        $I->assertEquals($firstName, $robot->first_name);
    }
}
