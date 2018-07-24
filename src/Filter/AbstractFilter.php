<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Filter;

use Assert\Assertion;
use Lmc\ApiFilter\Entity\Value;

abstract class AbstractFilter implements FilterInterface
{
    /** @var string */
    private $title;
    /** @var string */
    private $column;
    /** @var Value */
    private $value;

    public function __construct(string $title, string $column, Value $value)
    {
        Assertion::regex($title, '/^[a-z]+$/', 'Title must be only [a-z] letters but "%s" given.');
        $this->title = $title;
        $this->column = $column;
        $this->value = $value;
    }

    public function getTitle(): string
    {
        return $this->title;
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
