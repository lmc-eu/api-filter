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
        switch (mb_strtolower($filter)) {
            case Filter::EQUALS:
                return new FilterWithOperator($column, $value, '=', $title ?? Filter::EQUALS);
            case Filter::GREATER_THAN:
                return new FilterWithOperator($column, $value, '>', $title ?? Filter::GREATER_THAN);
            case Filter::LESS_THEN:
                return new FilterWithOperator($column, $value, '<', $title ?? Filter::LESS_THEN);
            case Filter::LESS_THEN_OR_EQUAL:
                return new FilterWithOperator($column, $value, '<=', $title ?? Filter::LESS_THEN_OR_EQUAL);
            case Filter::GREATER_THAN_OR_EQUAL:
                return new FilterWithOperator($column, $value, '>=', $title ?? Filter::GREATER_THAN_OR_EQUAL);
            case Filter::IN:
                return new FilterIn($column, $value, $title);
            case Filter::FUNCTION:
                return new FilterFunction($column, $value, $title);
            case Filter::FUNCTION_PARAMETER:
                return new FunctionParameter($column, $value, $title);
        }

        throw UnknownFilterException::forFilterWithColumnAndValue($filter, $column, $value);
    }
}
