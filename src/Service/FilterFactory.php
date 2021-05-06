<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service;

use Lmc\ApiFilter\Constant\Filter;
use Lmc\ApiFilter\Entity\Value;
use Lmc\ApiFilter\Exception\UnknownFilterException;
use Lmc\ApiFilter\Filter\FilterFunction;
use Lmc\ApiFilter\Filter\FilterIn;
use Lmc\ApiFilter\Filter\FilterInterface;
use Lmc\ApiFilter\Filter\FilterWithOperator;
use Lmc\ApiFilter\Filter\FunctionParameter;

class FilterFactory
{
    public function createFilter(string $column, string $filter, Value $value, string $title = null): FilterInterface
    {
        return match (mb_strtolower($filter)) {
            Filter::EQUALS => new FilterWithOperator($column, $value, '=', $title ?? Filter::EQUALS),
            Filter::NOT_EQUALS => new FilterWithOperator($column, $value, '!=', $title ?? Filter::NOT_EQUALS),
            Filter::GREATER_THAN => new FilterWithOperator($column, $value, '>', $title ?? Filter::GREATER_THAN),
            Filter::LESS_THEN => new FilterWithOperator($column, $value, '<', $title ?? Filter::LESS_THEN),
            Filter::LESS_THEN_OR_EQUAL => new FilterWithOperator(
                $column,
                $value,
                '<=',
                $title ?? Filter::LESS_THEN_OR_EQUAL
            ),
            Filter::GREATER_THAN_OR_EQUAL => new FilterWithOperator(
                $column,
                $value,
                '>=',
                $title ?? Filter::GREATER_THAN_OR_EQUAL
            ),
            Filter::IN => new FilterIn($column, $value, $title),
            Filter::FUNCTION => new FilterFunction($column, $value, $title),
            Filter::FUNCTION_PARAMETER => new FunctionParameter($column, $value, $title),
            default => throw UnknownFilterException::forFilterWithColumnAndValue($filter, $column, $value),
        };
    }
}
