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

namespace Phalcon\Incubator\MongoDB\Mvc\Test\Integration\Collection\Manager;

use IntegrationTester;

/**
 * Class NotifyEventCest
 */
class NotifyEventCest
{
    /**
     * Tests Phalcon\Mvc\Collection\Manager :: notifyEvent()
     *
     * @author Phalcon Team <team@phalcon.io>
     * @since  2018-11-13
     */
    public function mvcCollectionsManagerNotifyEvent(IntegrationTester $I)
    {
        $I->wantToTest('Mvc\Collection\Manager - notifyEvent()');
    }
}
