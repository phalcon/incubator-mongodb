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

namespace Phalcon\Incubator\MongoDB\Mvc\Test\Integration\Collection;

use IntegrationTester;

/**
 * Class FireEventCancelCest
 */
class FireEventCancelCest
{
    /**
     * Tests Phalcon\Mvc\Collection :: fireEventCancel()
     *
     * @param IntegrationTester $I
     * @since  2018-11-13
     * @author Phalcon Team <team@phalconphp.com>
     */
    public function mvcCollectionFireEventCancel(IntegrationTester $I)
    {
        $I->wantToTest('Mvc\Collection - fireEventCancel()');
}
}
