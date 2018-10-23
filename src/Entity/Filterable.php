<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Entity;

use Lmc\ApiFilter\Assertion;

class Filterable
{
    /** @var mixed */
    private $value;

    /** @param mixed $value This must be supported by any applicator */
    public function __construct($value)
    {
        Assertion::notIsInstanceOf(
            $value,
            self::class,
            'Filterable must not contain another Filterable. Extract a value from Filterable or use it directly.'
        );
        $this->value = $value;
    }

    /** @return mixed This must be supported by any applicator */
    public function getValue()
    {
        return $this->value;
    }
}
