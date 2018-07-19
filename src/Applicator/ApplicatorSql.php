<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Applicator;

use Lmc\ApiFilter\Entity\Filterable;
use Lmc\ApiFilter\Filter\FilterInterface;

class ApplicatorSql extends AbstractApplicator
{
    public function supports(Filterable $filterable): bool
    {
        return is_string($filterable->getValue());
    }

    /**
     * Apply filter to filterable and returns the result
     *
     * @example
     * $simpleSqlApplicator->apply(new FilterWithOperator('title', 'foo', '='), 'SELECT * FROM table')
     * // SELECT * FROM table WHERE 1 AND title = 'foo'
     */
    public function applyTo(FilterInterface $filter, Filterable $filterable): Filterable
    {
        $filterable = $filterable->getValue();
        $sql = mb_stripos($filterable, 'where') === false
            ? $filterable . ' WHERE 1'
            : $filterable;

        $column = $filter->getColumn();

        $result = sprintf(
            '%s AND %s %s %s',
            $sql,
            $column,
            $filter->getOperator(),
            $this->escape($column, $filter->getValue())->getValue()
        );

        return new Filterable($result);
    }
}
