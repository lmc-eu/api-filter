<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service;

use Lmc\ApiFilter\Assertion;
use Lmc\ApiFilter\Entity\Value;
use Lmc\ApiFilter\Exception\TupleException;
use Lmc\ApiFilter\Filters\Filters;
use Lmc\ApiFilter\Filters\FiltersInterface;
use MF\Collection\Exception\TupleExceptionInterface;
use MF\Collection\Immutable\ITuple;
use MF\Collection\Immutable\Seq;
use MF\Collection\Immutable\Tuple;

class QueryParametersParser
{
    /** @var FilterFactory */
    private $filterFactory;

    public function __construct(FilterFactory $filterFactory)
    {
        $this->filterFactory = $filterFactory;
    }

    public function parse(array $queryParameters): FiltersInterface
    {
        try {
            return Seq::init(function () use ($queryParameters) {
                foreach ($queryParameters as $column => $values) {
                    $columns = $this->parseColumns($column);
                    $columnsCount = count($columns);

                    foreach ($this->normalizeFilters($values) as $filter => $value) {
                        $this->assertTupleIsAllowed($filter, $columnsCount);
                        $parsedValues = $this->parseValues($value, $columnsCount);

                        foreach ($columns as $column) {
                            yield Tuple::of($column, $filter, new Value(array_shift($parsedValues)));
                        }
                    }
                }
            })
                ->reduce(
                    function (FiltersInterface $filters, ITuple $tuple): FiltersInterface {
                        return $filters->addFilter($this->filterFactory->createFilter(...$tuple));
                    },
                    new Filters()
                );
        } catch (TupleExceptionInterface $e) {
            throw TupleException::forBaseTupleException($e);
        }
    }

    private function parseColumns(string $column): array
    {
        return mb_substr($column, 0, 1) === '('
            ? Tuple::parse($column)->toArray()
            : [$column];
    }

    private function normalizeFilters($values): array
    {
        return is_array($values)
            ? $values
            : ['eq' => $values];
    }

    private function assertTupleIsAllowed(string $filter, int $columnsCount): void
    {
        Assertion::false($columnsCount > 1 && $filter === 'in', 'Tuples are not allowed in IN filter.');
    }

    private function parseValues($value, int $columnsCount): array
    {
        return $columnsCount > 1
            ? Tuple::parse($value, $columnsCount)->toArray()
            : [$value];
    }
}
