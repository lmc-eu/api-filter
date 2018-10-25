<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Entity;

use Assert\Assertion;

class Value
{
    /** @var mixed */
    private $value;

    /** @param mixed $value Value of different types */
    public function __construct($value)
    {
        Assertion::notIsInstanceOf(
            $value,
            self::class,
            'Value must not contain another Value. Extract a value from Value or use it directly.'
        );
        $this->value = $value;
    }

    /** @return mixed Value of different types */
    public function getValue()
    {
        return $this->value;
    }
}
