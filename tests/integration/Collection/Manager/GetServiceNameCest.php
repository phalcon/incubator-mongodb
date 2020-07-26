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

namespace Phalcon\Incubator\Mvc\Test\Integration\Collection\Manager;

use IntegrationTester;

/**
 * Class GetServiceNameCest
 */
class GetServiceNameCest
{
    /**
     * Tests Phalcon\Mvc\Collection\Manager :: getServiceName()
     *
     * @author Phalcon Team <team@phalconphp.com>
     * @since  2018-11-13
     */
    public function mvcCollectionManagerGetServiceName(IntegrationTester $I)
    {
        $I->wantToTest('Mvc\Collection\Manager - getServiceName()');
}
}
