<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Filters;

use Lmc\ApiFilter\Applicator\ApplicatorInterface;
use Lmc\ApiFilter\Entity\Filterable;
use Lmc\ApiFilter\Filter\FilterInterface;
use Lmc\ApiFilter\Filter\FunctionParameter;
use Lmc\ApiFilter\Service\FilterApplicator;
use MF\Collection\IEnumerable;

interface FiltersInterface extends IEnumerable
{
    /**
     * Apply all filters to given filterable
     */
    public function applyAllTo(Filterable $filterable, FilterApplicator $filterApplicator): Filterable;

    public function getPreparedValues(ApplicatorInterface $applicator): array;

    public function hasFilter(FilterInterface $filter): bool;

    public function addFilter(FilterInterface $filter): self;

    public function filterByColumns(array $columns): self;

    public function getFunctionParameter(string $parameter): FunctionParameter;

    public function toArray(): array;
}
