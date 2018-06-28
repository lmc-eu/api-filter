<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Escape;

use Lmc\ApiFilter\Entity\Value;

class EscapeString implements EscapeInterface
{
    public function supports(string $column, Value $value): bool
    {
        return is_string($value->getValue());
    }

    public function escape(string $column, Value $value): Value
    {
        return new Value(sprintf('\'%s\'', $value->getValue()));
    }
}
