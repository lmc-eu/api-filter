<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Filter;

use Lmc\ApiFilter\Entity\Value;

class FilterWithOperator extends AbstractFilter
{
    /** @var string */
    private $operator;

    public function __construct(string $column, Value $value, string $operator, string $title)
    {
        parent::__construct($title, $column, $value);
        $this->operator = $operator;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }
}
