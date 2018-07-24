<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Filters;

use Lmc\ApiFilter\Applicator\ApplicatorInterface;
use Lmc\ApiFilter\Entity\Filterable;
use Lmc\ApiFilter\Filter\FilterInterface;
use MF\Collection\Immutable\Generic\IList;
use MF\Collection\Immutable\Generic\ListCollection;

class Filters implements FiltersInterface
{
    /** @var IList|FilterInterface[] */
    private $filters;

    /** @param FilterInterface[] $filters */
    public static function from(array $filters): FiltersInterface
    {
        return new self($filters);
    }

    /** @param FilterInterface[] $filters */
    public function __construct(array $filters)
    {
        $this->filters = ListCollection::fromT(FilterInterface::class, $filters);
    }

    /**
     * Apply all filters to given filterable
     */
    public function applyAllTo(ApplicatorInterface $applicator, Filterable $filterable): Filterable
    {
        return $this->filters->reduce(
            function (Filterable $filterable, FilterInterface $filter) use ($applicator) {
                return $applicator->applyTo($filter, $filterable);
            },
            $filterable
        );
    }

    /** @return FilterInterface[] */
    public function getIterator(): iterable
    {
        yield from $this->filters;
    }

    public function getPreparedValues(ApplicatorInterface $applicator): array
    {
        $preparedValues = [];
        foreach ($this->filters as $filter) {
            $preparedValues += $applicator->getPreparedValue($filter);
        }

        return $preparedValues;
    }
}
