<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Entity;

class Value
{
    /** @var mixed */
    private $value;

    /** @param mixed $value Value of different types */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /** @return mixed Value of different types */
    public function getValue()
    {
        return $this->value;
    }
}
