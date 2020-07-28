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

namespace Phalcon\Incubator\MongoDB\Mvc\Test\Integration\Collection\Manager;

use IntegrationTester;

/**
 * Class SetConnectionServiceCest
 */
class SetConnectionServiceCest
{
    /**
     * Tests Phalcon\Mvc\Collection\Manager :: setConnectionService()
     *
     * @author Phalcon Team <team@phalconphp.com>
     * @since  2018-11-13
     */
    public function mvcCollectionManagerSetConnectionService(IntegrationTester $I)
    {
        $I->wantToTest('Mvc\Collection\Manager - setConnectionService()');
}
}
