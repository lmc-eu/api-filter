<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service;

use Lmc\ApiFilter\Entity\Value;
use Lmc\ApiFilter\Filter\FilterInterface;
use Lmc\ApiFilter\Filter\FilterWithOperator;
use Lmc\ApiFilter\Filters\Filters;
use Lmc\ApiFilter\Filters\FiltersInterface;
use MF\Collection\Immutable\Seq;
use MF\Collection\Immutable\Tuple;

class QueryParametersParser
{
    public function parse(array $queryParameters): FiltersInterface
    {
        $filters = Seq::init(function () use ($queryParameters) {
            foreach ($queryParameters as $column => $values) {
                $values = is_array($values)
                    ? $values
                    : ['eq' => $values];

                foreach ($values as $filter => $value) {
                    yield Tuple::of($column, $filter, new Value($value));
                }
            }
        })
            ->map(function (Tuple $tuple): FilterInterface {
                return $this->createFilter(...$tuple);
            })
            ->toArray();

        return Filters::from($filters);
    }

    private function createFilter(string $column, string $filter, Value $value): FilterInterface
    {
        switch (mb_strtolower($filter)) {
            case 'eq':
                return new FilterWithOperator($column, $value, '=', 'eq');
            case 'gt':
                return new FilterWithOperator($column, $value, '>', 'gt');
            case 'lt':
                return new FilterWithOperator($column, $value, '<', 'lt');
            case 'lte':
                return new FilterWithOperator($column, $value, '<=', 'lt');
            case 'gte':
                return new FilterWithOperator($column, $value, '>=', 'gte');
        }

        throw new \InvalidArgumentException(
            sprintf(
                'Filter "%s" is not implemented. For column "%s" with value "%s".',
                $filter,
                $column,
                $value->getValue()
            )
        );
    }
}
