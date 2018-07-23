<?php declare(strict_types=1);

namespace Lmc\ApiFilter;

use Doctrine\ORM\QueryBuilder;
use Mockery as m;

class ApiFilterTest extends AbstractTestCase
{
    /** @var ApiFilter */
    private $apiFilter;

    protected function setUp(): void
    {
        $this->apiFilter = new ApiFilter();
    }

    /**
     * @test
     * @dataProvider provideQueryParametersForSql
     */
    public function shouldParseQueryParametersAndApplyThemToSimpleSql(
        array $queryParameters,
        string $sql,
        string $expectedSql
    ): void {
        $filters = $this->apiFilter->parseFilters($queryParameters);
        $sqlWithFilters = $this->apiFilter->applyFilters($filters, $sql);

        $this->assertSame($expectedSql, $sqlWithFilters);
    }

    public function provideQueryParametersForSql(): array
    {
        return [
            // empty
            'empty' => [
                [],
                'SELECT * FROM table',
                'SELECT * FROM table',
            ],
            'title=foo' => [
                ['title' => 'foo'],
                'SELECT * FROM table',
                'SELECT * FROM table WHERE 1 AND title = \'foo\'',
            ],
            'title[eq]=foobar' => [
                ['title' => ['eq' => 'foo']],
                'SELECT * FROM table',
                'SELECT * FROM table WHERE 1 AND title = \'foo\'',
            ],
            'title[eq]=foobar&value[gt]=10' => [
                ['title' => ['eq' => 'foo'], 'value' => ['gt' => '10']],
                'SELECT * FROM table',
                'SELECT * FROM table WHERE 1 AND title = \'foo\' AND value > 10',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provideQueryParametersForSql
     */
    public function shouldParseQueryParametersAndApplyThemOneByOneToSimpleSql(
        array $queryParameters,
        string $sql,
        string $expectedSql
    ): void {
        $filters = $this->apiFilter->parseFilters($queryParameters);

        foreach ($filters as $filter) {
            $sql = $this->apiFilter->applyFilter($filter, $sql);
        }

        $this->assertSame($expectedSql, $sql);
    }

    /**
     * @test
     * @dataProvider provideQueryParametersForQueryBuilder
     */
    public function shouldParseQueryParametersAndApplyThemToQueryBuilder(
        array $queryParameters,
        string $expectedSql
    ): void {
        $this->markTestIncomplete('Todo - finish with imlementing apply method');
        $queryBuilder = m::mock(QueryBuilder::class);

        $filters = $this->apiFilter->parseFilters($queryParameters);
        $queryBuilderWithFilters = $this->apiFilter->applyFilters($filters, $queryBuilder);

        $this->assertInstanceOf(QueryBuilder::class, $queryBuilderWithFilters);
        //$this->assertSame($expectedSql, $queryBuilderWithFilters);
    }

    public function provideQueryParametersForQueryBuilder(): array
    {
        return [
            // empty
            'empty' => [
                [],
                'SELECT * FROM table',
                'SELECT * FROM table',
            ],
            'title=foo' => [
                ['title' => 'foo'],
                'SELECT * FROM table',
                'SELECT * FROM table WHERE 1 AND title = \'foo\'',
            ],
            'title[eq]=foobar' => [
                ['title' => ['eq' => 'foo']],
                'SELECT * FROM table',
                'SELECT * FROM table WHERE 1 AND title = \'foo\'',
            ],
            'title[eq]=foobar&value[gt]=10' => [
                ['title' => ['eq' => 'foo'], 'value' => ['gt' => '10']],
                'SELECT * FROM table',
                'SELECT * FROM table WHERE 1 AND title = \'foo\' AND value > 10',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provideQueryParametersForQueryBuilder
     */
    public function shouldParseQueryParametersAndApplyThemOneByOneToQueryBuilder(
        array $queryParameters,
        string $expectedSql
    ): void {
        $this->markTestIncomplete('Todo - finish with imlementing apply method');
        $queryBuilder = m::mock(QueryBuilder::class);

        $filters = $this->apiFilter->parseFilters($queryParameters);

        foreach ($filters as $filter) {
            $queryBuilder = $this->apiFilter->applyFilter($filter, $queryBuilder);
        }

        $this->assertInstanceOf(QueryBuilder::class, $queryBuilder);
        //$this->assertSame($expectedSql, $sql);
    }
}
