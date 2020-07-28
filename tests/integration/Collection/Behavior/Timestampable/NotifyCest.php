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

namespace Phalcon\Incubator\MongoDB\Mvc\Test\Integration\Collection\Behavior\Timestampable;

use IntegrationTester;

/**
 * Class NotifyCest
 */
class NotifyCest
{
    /**
     * Tests Phalcon\Mvc\Collection\Behavior\Timestampable :: notify()
     *
     * @author Phalcon Team <team@phalcon.io>
     * @since  2018-11-13
     */
    public function mvcCollectionBehaviorTimestampableNotify(IntegrationTester $I)
    {
        $I->wantToTest('Mvc\Collection\Behavior\Timestampable - notify()');
    }
}
