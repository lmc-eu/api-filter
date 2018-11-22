<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Constant;

final class Filter
{
    public const EQUALS = 'eq';
    public const LESS_THEN_OR_EQUAL = 'lte';
    public const LESS_THEN = 'lt';
    public const GREATER_THAN = 'gt';
    public const GREATER_THAN_OR_EQUAL = 'gte';
    public const IN = 'in';

    public const FUNCTION = 'function';
}
