<?php declare(strict_types=1);

namespace Lmc\ApiFilter;

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
        string $expectedSql,
        array $expectedPreparedValues
    ): void {
        $filters = $this->apiFilter->parseFilters($queryParameters);
        $sqlWithFilters = $this->apiFilter->applyFilters($filters, $sql);
        $preparedValues = $this->apiFilter->getPreparedValues($filters, $sql);

        $this->assertSame($expectedSql, $sqlWithFilters);
        $this->assertSame($expectedPreparedValues, $preparedValues);
    }

    public function provideQueryParametersForSql(): array
    {
        return [
            // query parameters, sql, expected sql, expected prepared values
            'empty' => [
                [],
                'SELECT * FROM table',
                'SELECT * FROM table',
                [],
            ],
            'title=foo' => [
                ['title' => 'foo'],
                'SELECT * FROM table',
                'SELECT * FROM table WHERE 1 AND title = :title_eq',
                ['title_eq' => 'foo'],
            ],
            'title[eq]=foobar' => [
                ['title' => ['eq' => 'foo']],
                'SELECT * FROM table',
                'SELECT * FROM table WHERE 1 AND title = :title_eq',
                ['title_eq' => 'foo'],
            ],
            'title[eq]=foobar&value[gt]=10' => [
                ['title' => ['eq' => 'foo'], 'value' => ['gt' => '10']],
                'SELECT * FROM table',
                'SELECT * FROM table WHERE 1 AND title = :title_eq AND value > :value_gt',
                ['title_eq' => 'foo', 'value_gt' => '10'],
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
        string $expectedSql,
        array $expectedPreparedValues
    ): void {
        $filters = $this->apiFilter->parseFilters($queryParameters);

        $preparedValues = [];
        foreach ($filters as $filter) {
            $sql = $this->apiFilter->applyFilter($filter, $sql);
            $preparedValues += $this->apiFilter->getPreparedValue($filter, $sql);
        }

        $this->assertSame($expectedSql, $sql);
        $this->assertSame($expectedPreparedValues, $preparedValues);
    }
}
