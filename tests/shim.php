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

use Dotenv\Dotenv;

if (!function_exists('env')) {
    function env(string $key, $default = null)
    {
        if (defined($key)) {
            return constant($key);
        }

        return getenv($key) ?: $default;
    }
}

/**
 * Calls .env and merges the global and local configurations
 */
if (!function_exists('loadEnvironment')) {
    function loadEnvironment(string $root)
    {
        /**
         * Load local environment if it exists
         */
        (new Dotenv($root, 'tests/_ci/.env.default'))->load();

        /**
         * Necessary evil. We need to set some constants for INI files to work
         */
        defineFromEnv('DATA_MYSQL_CHARSET');
        defineFromEnv('DATA_MYSQL_HOST');
        defineFromEnv('DATA_MYSQL_NAME');
        defineFromEnv('DATA_MYSQL_PASS');
        defineFromEnv('DATA_MYSQL_PORT');
        defineFromEnv('DATA_MYSQL_USER');
        defineFromEnv('PATH_CACHE');
        defineFromEnv('PATH_DATA');
        defineFromEnv('PATH_OUTPUT');
    }
}

if (!function_exists('defineFromEnv')) {
    function defineFromEnv(string $name)
    {
        if (defined($name)) {
            return;
        }

        define(
            $name,
            env($name)
        );
    }
}

/**
 * Ensures that certain folders are always ready for us.
 */
if (!function_exists('loadFolders')) {
    function loadFolders()
    {
        $folders = [
            'annotations',
            'assets',
            'cache',
            'cache/models',
            'image',
            'image/gd',
            'image/imagick',
            'logs',
            'session',
            'stream',
        ];

        foreach ($folders as $folder) {
            $item = outputDir('tests/' . $folder);

            if (true !== file_exists($item)) {
                mkdir($item, 0777, true);
            }
        }
    }
}

/**
 * Returns the output folder
 */
if (!function_exists('outputDir')) {
    function outputDir(string $fileName = ''): string
    {
        return codecept_output_dir() . $fileName;
    }
}

/*******************************************************************************
 * Options
 *******************************************************************************/
if (true !== function_exists('getOptionsMongo')) {
    /**
     * Get mongodb options
     */
    function getOptionsMongo(): array
    {
        return [
            'host'     => env('DATA_MONGO_HOST', 'mongodb'),
            'dbname'   => env('DATA_MONGO_NAME', 'tests'),
            'username' => env('DATA_MONGO_USER'),
            'password' => env('DATA_MONGO_PASS'),
        ];
    }
}
