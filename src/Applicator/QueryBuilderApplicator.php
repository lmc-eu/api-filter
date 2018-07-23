<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Applicator;

use Doctrine\ORM\QueryBuilder;
use Lmc\ApiFilter\Entity\Filterable;
use Lmc\ApiFilter\Filter\FilterIn;
use Lmc\ApiFilter\Filter\FilterInterface;
use Lmc\ApiFilter\Filter\FilterWithOperator;

class QueryBuilderApplicator extends AbstractApplicator
{
    public function supports(Filterable $filterable): bool
    {
        return $filterable->getValue() instanceof QueryBuilder;
    }

    /**
     * Apply filter with operator to filterable and returns the result
     *
     * @example
     * $filter = new FilterWithOperator('title', 'foo', '=', 'eq');
     * $queryBuilder = $this->createQueryBuilder('t');
     *
     * $queryBuilderApplicator->applyFilterWithOperator($filter, $queryBuilder); // SELECT * FROM table t WHERE t.title = :title_eq
     * $preparedValues = $queryBuilderApplicator->getPreparedValue($filter);     // ['title_eq' => 'foo']
     * $queryBuilder->setParameters($preparedValues);
     */
    public function applyFilterWithOperator(FilterWithOperator $filter, Filterable $filterable): Filterable
    {
        /** @var QueryBuilder $queryBuilder */
        [$queryBuilder, $alias] = $this->getQueryBuilder($filterable);

        $expr = sprintf(
            '%s.%s %s %s',
            $alias,
            $filter->getColumn(),
            $filter->getOperator(),
            $this->getColumnSinglePlaceholder(':', $filter)
        );

        return new Filterable($queryBuilder->andWhere($expr));
    }

    private function getQueryBuilder(Filterable $filterable): array
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = clone $filterable->getValue();
        [$alias] = $queryBuilder->getAllAliases();

        return [$queryBuilder, $alias];
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
        /** @var QueryBuilder $queryBuilder */
        [$queryBuilder, $alias] = $this->getQueryBuilder($filterable);

        $expr = sprintf(
            '%s.%s IN (%s)',
            $alias,
            $filter->getColumn(),
            $this->getColumnSinglePlaceholder(':', $filter)
        );

        return new Filterable($queryBuilder->andWhere($expr));
    }

    public function getPreparedValue(FilterInterface $filter): array
    {
        return $this->getPreparedSingleValue($filter);
    }
}
