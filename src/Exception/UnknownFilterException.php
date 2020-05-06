<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Exception;

use Lmc\ApiFilter\Entity\Value;

class UnknownFilterException extends InvalidArgumentException
{
    public static function forFilterWithColumnAndValue(string $filter, string $column, Value $value): self
    {
        return new self(
            sprintf(
                'Filter "%s" is not implemented. For column "%s" with value "%s".',
                $filter,
                $column,
                is_callable($value->getValue())
                    ? 'callable'
                    : $value->getValue()
            )
        );
    }
}
