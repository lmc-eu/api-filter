<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Filters;

use Lmc\ApiFilter\Applicator\ApplicatorInterface;
use Lmc\ApiFilter\Entity\Filterable;

interface FiltersInterface extends \IteratorAggregate
{
    /**
     * Apply all filters to given filterable
     */
    public function applyAllTo(ApplicatorInterface $applicator, Filterable $filterable): Filterable;

    public function getPreparedValues(ApplicatorInterface $applicator): array;
}
