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

namespace Phalcon\Incubator\MongoDB\Test\Fixtures\Mvc\Collections;

use Phalcon\Filter\Validation;
use Phalcon\Filter\Validation\Validator\Numericality;
use Phalcon\Incubator\MongoDB\Mvc\Collection;
use Phalcon\Incubator\MongoDB\Test\Fixtures\Mvc\Collections\Documents\RobotPart;
use Phalcon\Messages\Message;

class Robots extends Collection
{
    public $first_name;
    public $last_name;
    public $version = 1;
    public $sub = [
        'bool' => false,
        'float' => 0.02,
    ];

    public $rbpart;

    protected $protected_field = 42;
    private $private_field;

    protected static array $typeMap = [
        'fieldPaths' => [
            'rbpart' => RobotPart::class,
        ]
    ];

    public function validation()
    {
        $validation = new Validation();
        $validation->add("protected_field", new Numericality([
            'message' => 'protected_field must be numeric',
        ]));

        if ($this->version < 1) {
            $message = new Message("The version must be greater than 1", "version");
            $this->appendMessage($message);

            return false;
        }

        return $this->validate($validation);
    }

    /**
     * @return mixed
     */
    public function getProtectedField()
    {
        return $this->protected_field;
    }

    /**
     * @param mixed $protected_field
     */
    public function setProtectedField($protected_field): void
    {
        $this->protected_field = $protected_field + 1;
    }

    /**
     * @return mixed
     */
    public function getPrivateField()
    {
        return $this->private_field;
    }

    /**
     * @param mixed $private_field
     */
    public function setPrivateField($private_field): void
    {
        $this->private_field = $private_field;
    }

    /**
     * @return array
     */
    public function revealObjectVars(): array
    {
        // Ignore private field for <Mvc\Collection - getReservedAttributes()> test
        return array_diff_key(get_object_vars($this), [
            'private_field' => null
        ]);
    }
}
