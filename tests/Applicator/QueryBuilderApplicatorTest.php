<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Applicator;

use Doctrine\ORM\QueryBuilder;
use Lmc\ApiFilter\AbstractTestCase;
use Lmc\ApiFilter\Entity\Filterable;
use Lmc\ApiFilter\Entity\Value;
use Lmc\ApiFilter\Filter\FilterWithOperator;

class QueryBuilderApplicatorTest extends AbstractTestCase
{
    /** @var QueryBuilderApplicator */
    private $queryBuilderApplicator;
    /** @var QueryBuilder */
    private $queryBuilder;

    protected function setUp(): void
    {
        $this->queryBuilder = $this->setUpQueryBuilder('t');
        $this->queryBuilderApplicator = new QueryBuilderApplicator();
    }

    /**
     * @test
     */
    public function shouldSupportStringFilterable(): void
    {
        $filterable = new Filterable($this->queryBuilder);

        $result = $this->queryBuilderApplicator->supports($filterable);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function shouldApplyFilterWithOperator(): void
    {
        $filter = new FilterWithOperator('col', new Value('val'), '=', 'eq');
        $filterable = new Filterable($this->queryBuilder);
        $expectedDqlWhere = ['t.col = :col_eq'];

        $result = $this->queryBuilderApplicator->applyFilterWithOperator($filter, $filterable);
        $queryBuilderWithFilters = $result->getValue();

        $this->assertDqlWhere($expectedDqlWhere, $queryBuilderWithFilters);
    }

    /**
     * @test
     */
    public function shouldPrepareValues(): void
    {
        $filter = new FilterWithOperator('col', new Value('val'), '=', 'eq');

        $result = $this->queryBuilderApplicator->getPreparedValue($filter);

        $this->assertSame(['col_eq' => 'val'], $result);
    }

    /**
     * @test
     */
    public function shouldPrepareMultiValue(): void
    {
        $filter = new FilterWithOperator('col', new Value([1, 2]), '=', 'eq');

        $result = $this->queryBuilderApplicator->getPreparedValue($filter);

        $this->assertSame(['col_eq' => [1, 2]], $result);
    }
}
