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
use Phalcon\Incubator\MongoDB\Mvc\Collection\Exception;
use Phalcon\Messages\MessageInterface;
use Phalcon\Incubator\MongoDB\Test\Fixtures\Mvc\Collections\Robots;
use Phalcon\Incubator\MongoDB\Test\Fixtures\Traits\DiTrait;

class AppendMessageCest
{
    use DiTrait;

    public function _before()
    {
        $this->setNewFactoryDefault();
        $this->setDiCollectionManager();
        $this->setDiMongo();
    }

    /**
     * Tests Phalcon\Mvc\Collection :: appendMessage()
     *
     * @param IntegrationTester $I
     * @throws Exception
     * @since  2018-11-13
     * @author Phalcon Team <team@phalcon.io>
     */
    public function mvcCollectionAppendMessage(IntegrationTester $I)
    {
        $I->wantToTest('Mvc\Collection - appendMessage()');

        $robot = new Robots;
        $robot->version = 0; // If version < 0, message appened !

        $I->assertFalse($robot->save());
        $I->assertNotEmpty($robot->getMessages());
        $I->assertInstanceOf(MessageInterface::class, $robot->getMessages()[0]);
    }
}
