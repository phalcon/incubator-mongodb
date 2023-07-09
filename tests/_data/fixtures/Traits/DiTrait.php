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

namespace Phalcon\Incubator\MongoDB\Test\Fixtures\Traits;

use MongoDB\Client;
use Phalcon\Di\Di;
use Phalcon\Di\DiInterface;
use Phalcon\Di\FactoryDefault;
use Phalcon\Incubator\MongoDB\Mvc\Collection\Manager as CollectionsManager;

trait DiTrait
{
    /**
     * @var null|DiInterface
     */
    protected $container = null;

    /**
     * @return DiInterface|null
     */
    protected function getDi()
    {
        return $this->container;
    }

    /**
     * Set up a new DI
     */
    protected function newDi()
    {
        Di::reset();
        $this->container = new Di();
        Di::setDefault($this->container);
    }

    /**
     * Reset the DI
     */
    protected function resetDi()
    {
        Di::reset();
    }

    /**
     * Set up a new Collection Manager
     */
    protected function setDiCollectionsManager()
    {
        $this->container->setShared('collectionsManager', CollectionsManager::class);
    }

    /**
     * Set up mongo service
     */
    protected function setDiMongo()
    {
        if (!empty($_ENV['DATA_MONGO_USER']) && !empty($_ENV['DATA_MONGO_PASS'])) {
            $dsn = sprintf(
                'mongodb://%s:%s@%s:%d/?authSource=admin',
                $_ENV['DATA_MONGO_USER'],
                $_ENV['DATA_MONGO_PASS'],
                $_ENV['DATA_MONGO_HOST'],
                $_ENV['DATA_MONGO_PORT'],
            );
        } else {
            $dsn = sprintf(
                'mongodb://%s:%d',
                $_ENV['DATA_MONGO_HOST'],
                $_ENV['DATA_MONGO_PORT'],
            );
        }

        //var_dump($dsn); exit;

        $mongo = new Client($dsn);

        $this->container->setShared(
            'mongo',
            $mongo->selectDatabase($_ENV['DATA_MONGO_NAME'])
        );
    }

    /**
     * Set up a new FactoryDefault
     */
    protected function setNewFactoryDefault()
    {
        Di::reset();
        $this->container = $this->newFactoryDefault();
        Di::setDefault($this->container);
    }

    protected function newFactoryDefault(): FactoryDefault
    {
        return new FactoryDefault();
    }

    /**
     * Return a service from the container
     * @param string $name
     * @return mixed
     */
    protected function getService(string $name)
    {
        return $this->container->get($name);
    }
}
