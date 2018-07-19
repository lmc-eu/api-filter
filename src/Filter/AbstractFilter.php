<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Filter;

use Lmc\ApiFilter\Entity\Value;

abstract class AbstractFilter implements FilterInterface
{
    /** @var string */
    private $column;
    /** @var Value */
    private $value;

    public function __construct(string $column, Value $value)
    {
        $this->column = $column;
        $this->value = $value;
    }

    public function getColumn(): string
    {
        return $this->column;
    }

    public function getValue(): Value
    {
        return $this->value;
    }
}
