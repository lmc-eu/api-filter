<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Applicator;

use Lmc\ApiFilter\Entity\Filterable;
use Lmc\ApiFilter\Filter\FilterIn;
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
        $result = sprintf(
            '%s AND %s %s %s',
            $this->getSql($filterable),
            $filter->getColumn(),
            $filter->getOperator(),
            $this->getColumnSinglePlaceholder(':', $filter)
        );

        return new Filterable($result);
    }

    private function getSql(Filterable $filterable): string
    {
        $filterable = $filterable->getValue();

        return mb_stripos($filterable, 'where') === false
            ? $filterable . ' WHERE 1'
            : $filterable;
    }

    /**
     * Apply IN filter to filterable and returns the result
     *
     * @example
     * $filter = new FilterIn('id', [1, 2]);
     * $sql = 'SELECT * FROM table';
     *
     * $sql = $simpleSqlApplicator->applyFilterIn($filter, $sql);         // SELECT * FROM table WHERE id IN (:id_in_0, :id_in_1)
     * $preparedValues = $simpleSqlApplicator->getPreparedValue($filter); // ['id_in_0' => 1, 'id_in_1' => 2]
     */
    public function applyFilterIn(FilterIn $filter, Filterable $filterable): Filterable
    {
        $result = sprintf(
            '%s AND %s IN (%s)',
            $this->getSql($filterable),
            $filter->getColumn(),
            $this->getColumnMultiPlaceholders(':', $filter)
        );

        return new Filterable($result);
    }
}
