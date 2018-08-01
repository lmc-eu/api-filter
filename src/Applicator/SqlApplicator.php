<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Applicator;

use Lmc\ApiFilter\Entity\Filterable;
use Lmc\ApiFilter\Filter\FilterWithOperator;

class SqlApplicator extends AbstractApplicator
{
    public function supports(Filterable $filterable): bool
    {
        return is_string($filterable->getValue());
    }

    /**
     * Apply filter with operator to filterable and returns the result
     *
     * @example
     * $filter = new FilterWithOperator('title', 'foo', '=', 'eq');
     * $sql = 'SELECT * FROM table';
     *
     * $simpleSqlApplicator->applyFilterWithOperator($filter, $sql);      // SELECT * FROM table WHERE title = :title_eq
     * $preparedValues = $simpleSqlApplicator->getPreparedValue($filter); // ['title_eq' => 'foo']
     */
    public function applyFilterWithOperator(FilterWithOperator $filter, Filterable $filterable): Filterable
    {
        $filterable = $filterable->getValue();
        $sql = mb_stripos($filterable, 'where') === false
            ? $filterable . ' WHERE 1'
            : $filterable;

        $result = sprintf(
            '%s AND %s %s %s',
            $sql,
            $filter->getColumn(),
            $filter->getOperator(),
            $this->getColumnSinglePlaceholder(':', $filter)
        );

        return new Filterable($result);
    }
}
