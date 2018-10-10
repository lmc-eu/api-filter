<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Entity;

class Filterable
{
    /** @var mixed */
    private $value;

    /** @param mixed $value This must be supported by any applicator */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /** @return mixed This must be supported by any applicator */
    public function getValue()
    {
        return $this->value;
    }
}
