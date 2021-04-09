<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Entity;

use Lmc\ApiFilter\Assertion;

class Filterable
{
    /** @param mixed $value This must be supported by any applicator */
    public function __construct(private mixed $value)
    {
        Assertion::notIsInstanceOf(
            $value,
            self::class,
            'Filterable must not contain another Filterable. Extract a value from Filterable or use it directly.'
        );
    }

    /** @return mixed This must be supported by any applicator */
    public function getValue(): mixed
    {
        return $this->value;
    }
}
