<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service;

use Lmc\ApiFilter\Assertion;
use Lmc\ApiFilter\Constant\Priority;
use Lmc\ApiFilter\Entity\Value;
use Lmc\ApiFilter\Exception\TupleException;
use Lmc\ApiFilter\Filters\Filters;
use Lmc\ApiFilter\Filters\FiltersInterface;
use Lmc\ApiFilter\Service\Parser\ParserInterface;
use Lmc\ApiFilter\Service\Parser\SingleColumnArrayValueParser;
use Lmc\ApiFilter\Service\Parser\SingleColumnSingleValueParser;
use Lmc\ApiFilter\Service\Parser\TupleColumnTupleValueParser;
use MF\Collection\Exception\TupleExceptionInterface;
use MF\Collection\Immutable\ITuple;
use MF\Collection\Immutable\Seq;
use MF\Collection\Immutable\Tuple;
use MF\Collection\Mutable\Generic\PrioritizedCollection;

class QueryParametersParser
{
    /**
     * @deprecated this should be used from parsers
     * @var FilterFactory
     */
    private $filterFactory;
    /** @var PrioritizedCollection|ParserInterface[] */
    private $parsers;

    public function __construct(FilterFactory $filterFactory)
    {
        $this->filterFactory = $filterFactory;

        $this->parsers = new PrioritizedCollection(ParserInterface::class);
        $this->parsers->add(new TupleColumnTupleValueParser($filterFactory), Priority::HIGH);
        $this->parsers->add(new SingleColumnArrayValueParser($filterFactory), Priority::LOWER);
        $this->parsers->add(new SingleColumnSingleValueParser($filterFactory), Priority::LOWEST);
    }

    public function parse(array $queryParameters): FiltersInterface
    {
        try {
            $filters = new Filters();
            foreach ($this->parseFilters($queryParameters) as $filter) {
                $filters->addFilter($filter);
            }

            $filters = $this->parseOld($queryParameters, $filters);

            return $filters;
        } catch (TupleExceptionInterface $e) {
            throw TupleException::forBaseTupleException($e);
        }
    }

    private function parseFilters(array $queryParameters): iterable
    {
        foreach ($queryParameters as $rawColumn => $rawValue) {
            foreach ($this->parsers as $parser) {
                if ($parser->supports($rawColumn, $rawValue)) {
                    yield from $parser->parse($rawColumn, $rawValue);

                    // continue to next query parameter
                    continue 2;
                }
            }
        }
    }

    /**
     * @deprecated this should be done by parsers
     */
    private function parseOld(array $queryParameters, FiltersInterface $filters): FiltersInterface
    {
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
                    $filter = $this->filterFactory->createFilter(...$tuple);

                    return $filters->hasFilter($filter)
                        ? $filters
                        : $filters->addFilter($filter);
                },
                $filters
            );
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
