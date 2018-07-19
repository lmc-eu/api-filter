<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Escape;

use Lmc\ApiFilter\Entity\Value;

class EscapeInt implements EscapeInterface
{
    public function supports(string $column, Value $value): bool
    {
        $value = $value->getValue();

        return is_int($value) || (is_string($value) && is_numeric($value) && mb_strpos('.', $value) === false);
    }

    public function escape(string $column, Value $value): Value
    {
        return new Value((int) $value->getValue());
    }
}
