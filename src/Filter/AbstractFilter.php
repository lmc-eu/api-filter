<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Filter;

use Lmc\ApiFilter\Assertion;
use Lmc\ApiFilter\Entity\Value;

abstract class AbstractFilter implements FilterInterface
{
    private string $title;
    private string $column;
    private Value $value;
    private ?string $fullTitle = null;

    public function __construct(string $title, string $column, Value $value)
    {
        Assertion::regex($title, '/^[a-zA-Z_]+$/', 'Title must be only [a-zA-Z_] letters but "%s" given.');
        $this->title = $title;
        $this->column = $column;
        $this->value = $value;
    }

    public function getTitle(): string
    {
        return $this->fullTitle !== null
            ? $this->fullTitle
            : implode('_', [$this->getColumn(), $this->title]);
    }

    public function getColumn(): string
    {
        return $this->column;
    }

    public function getValue(): Value
    {
        return $this->value;
    }

    protected function setValue(Value $value): void
    {
        $this->value = $value;
    }

    public function setFullTitle(string $title): void
    {
        $this->fullTitle = $title;
    }
}
