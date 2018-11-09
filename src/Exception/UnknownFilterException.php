<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Exception;

use Lmc\ApiFilter\Entity\Value;

class UnknownFilterException extends InvalidArgumentException
{
    public static function forFilterWithColumnAndValue(string $filter, string $column, Value $value): self
    {
        return new static(
            sprintf(
                'Filter "%s" is not implemented. For column "%s" with value "%s".',
                $filter,
                $column,
                $value->getValue()
            )
        );
    }
}
