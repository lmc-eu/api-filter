<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Entity;

use Lmc\ApiFilter\Assertion;

class Value
{
    public function __construct(private mixed $value)
    {
        Assertion::notIsInstanceOf(
            $value,
            self::class,
            'Value must not contain another Value. Extract a value from Value or use it directly.'
        );
    }

    public function getValue(): mixed
    {
        return $this->value;
    }
}
