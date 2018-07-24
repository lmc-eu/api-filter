<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Applicator;

use Lmc\ApiFilter\AbstractTestCase;
use Lmc\ApiFilter\Entity\Filterable;
use Lmc\ApiFilter\Entity\Value;
use Lmc\ApiFilter\Filter\FilterWithOperator;

class ApplicatorSqlTest extends AbstractTestCase
{
    /** @var ApplicatorSql */
    private $sqlApplicator;

    protected function setUp(): void
    {
        $this->sqlApplicator = new ApplicatorSql();
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
        $filter = new FilterWithOperator('col', new Value('val'), '=');
        $filterable = new Filterable('SELECT * FROM table');

        $result = $this->sqlApplicator->applyTo($filter, $filterable);

        $this->assertSame('SELECT * FROM table WHERE 1 AND col = val', $result->getValue());
    }
}
