<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Applicator;

use Lmc\ApiFilter\Entity\Filterable;
use Lmc\ApiFilter\Escape\EscapeInterface;
use Lmc\ApiFilter\Filter\FilterInterface;

interface ApplicatorInterface
{
    public function supports(Filterable $filterable): bool;

    /**
     * Apply filter to filterable and returns the result
     *
     * @example
     * $simpleSqlApplicator->apply(new FilterWithOperator('title', 'foo', '='), 'SELECT * FROM table')
     * // SELECT * FROM table WHERE 1 AND title = 'foo'
     */
    public function applyTo(FilterInterface $filter, Filterable $filterable): Filterable;

    public function setEscape(EscapeInterface $escape): void;

    /**
     * Prepared values for applied filter
     *
     * @example
     * $filterWithOperator = new FilterWithOperator('title', 'foo', '=', 'eq');
     * $preparedValues = $simpleSqlApplicator->getPreparedValue($filter);  // ['title_eq' => 'foo']
     *
     * @example
     * $filter = new FilterIn('id', [1, 2]);
     * $preparedValues = $simpleSqlApplicator->getPreparedValue($filter);  // ['id_in_0' => 1, 'id_in_1' => 2]
     */
    public function getPreparedValue(FilterInterface $filter): array;
}
