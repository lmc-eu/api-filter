<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Applicator;

use Doctrine\ORM\QueryBuilder;
use Lmc\ApiFilter\Entity\Filterable;
use Lmc\ApiFilter\Escape\EscapeInterface;
use Lmc\ApiFilter\Filter\FilterInterface;

class DoctrineQueryBuilderApplicator implements ApplicatorInterface
{
    public function supports(Filterable $filterable): bool
    {
        return $filterable->getValue() instanceof QueryBuilder;
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
        throw new \Exception(sprintf('Method %s is not implemented yet.', __METHOD__));
    }

    public function setEscape(EscapeInterface $escape): void
    {
        // this applicator does not supports custom escaping
    }
}
