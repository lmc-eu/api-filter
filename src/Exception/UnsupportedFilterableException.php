<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Exception;

use Lmc\ApiFilter\Entity\Filterable;

class UnsupportedFilterableException extends InvalidArgumentException
{
    public static function forFilterable(Filterable $filterable): self
    {
        $filterableValue = $filterable->getValue();

        return new static(
            sprintf(
                'Unsupported filterable of type "%s".',
                is_object($filterableValue)
                    ? get_class($filterableValue)
                    : gettype($filterableValue)
            )
        );
    }
}
