<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Applicator;

use Lmc\ApiFilter\AbstractTestCase;
use Lmc\ApiFilter\Entity\Filterable;
use Lmc\ApiFilter\Entity\Value;
use Lmc\ApiFilter\Filter\FilterWithOperator;

class SqlApplicatorTest extends AbstractTestCase
{
    /** @var SqlApplicator */
    private $sqlApplicator;

    protected function setUp(): void
    {
        $this->sqlApplicator = new SqlApplicator();
    }

    /**
     * @test
     */
    public function shouldSupportStringFilterable(): void
    {
        $filterable = new Filterable('SELECT * FROM table');

        $result = $this->sqlApplicator->supports($filterable);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function shouldApplyFilterWithOperator(): void
    {
        $filter = new FilterWithOperator('col', new Value('val'), '=', 'eq');
        $filterable = new Filterable('SELECT * FROM table');

        $result = $this->sqlApplicator->applyFilterWithOperator($filter, $filterable);

        $this->assertSame('SELECT * FROM table WHERE 1 AND col = :col_eq', $result->getValue());
    }

    /**
     * @test
     */
    public function shouldPrepareValues(): void
    {
        $filter = new FilterWithOperator('col', new Value('val'), '=', 'eq');

        $result = $this->sqlApplicator->getPreparedValue($filter);

        $this->assertSame(['col_eq' => 'val'], $result);
    }

    /**
     * @test
     */
    public function shouldPrepareMultiValue(): void
    {
        $filter = new FilterWithOperator('col', new Value([1, 2]), '=', 'eq');

        $result = $this->sqlApplicator->getPreparedValue($filter);

        $this->assertSame(['col_eq_0' => 1, 'col_eq_1' => 2], $result);
    }
}
