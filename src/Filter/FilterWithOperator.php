<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Filter;

use Lmc\ApiFilter\Entity\Value;

class FilterWithOperator extends AbstractFilter
{
    public function __construct(string $column, Value $value, private string $operator, string $title)
    {
        parent::__construct($title, $column, $value);
    }

    public function getOperator(): string
    {
        return $this->operator;
    }
}
