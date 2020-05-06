<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Exception;

use Lmc\ApiFilter\Filter\FilterInterface;

class UnsupportedFilterException extends InvalidArgumentException
{
    public static function forFilter(FilterInterface $filter): self
    {
        return new self(sprintf('Unsupported filter given "%s".', get_class($filter)));
    }
}
