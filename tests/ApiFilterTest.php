<?php declare(strict_types=1);

namespace Lmc\ApiFilter;

use Doctrine\ORM\QueryBuilder;
use Lmc\ApiFilter\Applicator\SqlApplicator;
use Lmc\ApiFilter\Constant\Priority;
use Lmc\ApiFilter\Entity\Value;
use Lmc\ApiFilter\Exception\ApiFilterExceptionInterface;
use Lmc\ApiFilter\Filter\FilterWithOperator;
use Lmc\ApiFilter\Filters\Filters;

class ApiFilterTest extends AbstractTestCase
{
    /** @var ApiFilter */
    private $apiFilter;
    /** @var QueryBuilder */
    private $queryBuilder;

    protected function setUp(): void
    {
        $this->queryBuilder = $this->setUpQueryBuilder();

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
        $this->registerSQLApplicator();

        $filters = $this->apiFilter->parseFilters($queryParameters);
        $sqlWithFilters = $this->apiFilter->applyFilters($filters, $sql);
        $preparedValues = $this->apiFilter->getPreparedValues($filters, $sql);

        $this->assertSame($expectedSql, $sqlWithFilters);
        $this->assertSame($expectedPreparedValues, $preparedValues);
    }

    private function registerSQLApplicator(): void
    {
        $this->apiFilter->registerApplicator(new SqlApplicator(), Priority::MEDIUM);
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
        $this->registerSQLApplicator();

        $filters = $this->apiFilter->parseFilters($queryParameters);

        $preparedValues = [];
        foreach ($filters as $filter) {
            $sql = $this->apiFilter->applyFilter($filter, $sql);
            $preparedValues += $this->apiFilter->getPreparedValue($filter, $sql);
        }

        $this->assertSame($expectedSql, $sql);
        $this->assertSame($expectedPreparedValues, $preparedValues);
    }

    /**
     * @test
     * @dataProvider provideQueryParametersForQueryBuilder
     */
    public function shouldParseQueryParametersAndApplyThemToQueryBuilder(
        array $queryParameters,
        ?array $expectedDqlWhere,
        array $expectedPreparedValues
    ): void {
        $filters = $this->apiFilter->parseFilters($queryParameters);

        /** @var QueryBuilder $queryBuilderWithFilters */
        $queryBuilderWithFilters = $this->apiFilter->applyFilters($filters, $this->queryBuilder);
        $preparedValues = $this->apiFilter->getPreparedValues($filters, $this->queryBuilder);

        if (!empty($queryParameters)) {
            // application of filters should not change original query builder
            $this->assertNotSame($this->queryBuilder, $queryBuilderWithFilters);
        }

        $this->assertInstanceOf(QueryBuilder::class, $queryBuilderWithFilters);
        $this->assertDqlWhere($expectedDqlWhere, $queryBuilderWithFilters);
        $this->assertSame($expectedPreparedValues, $preparedValues);
    }

    public function provideQueryParametersForQueryBuilder(): array
    {
        return [
            // query parameters, expected dql where, expected prepared values
            'empty' => [
                [],
                null,
                [],
            ],
            'title=foo' => [
                ['title' => 'foo'],
                ['t.title = :title_eq'],
                ['title_eq' => 'foo'],
            ],
            'title[eq]=foobar' => [
                ['title' => ['eq' => 'foo']],
                ['t.title = :title_eq'],
                ['title_eq' => 'foo'],
            ],
            'title[eq]=foobar&value[gt]=10' => [
                ['title' => ['eq' => 'foo'], 'value' => ['gt' => '10']],
                ['t.title = :title_eq', 't.value > :value_gt'],
                ['title_eq' => 'foo', 'value_gt' => '10'],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provideQueryParametersForQueryBuilder
     */
    public function shouldParseQueryParametersAndApplyThemOneByOneToQueryBuilder(
        array $queryParameters,
        ?array $expectedDqlWhere,
        array $expectedPreparedValues
    ): void {
        $filters = $this->apiFilter->parseFilters($queryParameters);

        foreach ($filters as $filter) {
            $this->queryBuilder = $this->apiFilter->applyFilter($filter, $this->queryBuilder);
        }
        $preparedValues = $this->apiFilter->getPreparedValues($filters, $this->queryBuilder);

        $this->assertInstanceOf(QueryBuilder::class, $this->queryBuilder);
        $this->assertDqlWhere($expectedDqlWhere, $this->queryBuilder);
        $this->assertSame($expectedPreparedValues, $preparedValues);
    }

    /**
     * @test
     * @dataProvider provideInvalidQueryParameters
     */
    public function shouldNotParseInvalidQueryParameters(array $invalidQueryParameters, string $expectedMessage): void
    {
        $this->expectException(ApiFilterExceptionInterface::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->apiFilter->parseFilters($invalidQueryParameters);
    }

    public function provideInvalidQueryParameters(): array
    {
        return [
            // invalidQueryParameters, expectedMessage
            'empty filter' => [
                ['column' => ['' => 'value']],
                'Filter "" is not implemented. For column "column" with value "value".',
            ],
            'unknown filter' => [
                ['column' => ['unknown' => 'value']],
                'Filter "unknown" is not implemented. For column "column" with value "value".',
            ],
            'tuples in IN filter' => [
                ['(id, name)' => ['in' => ['(1,one)', '(2,two)']]],
                'Tuples are not allowed in IN filter.',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provideNotSupportedFilterable
     *
     * @param mixed $filterable of type <T>
     */
    public function shouldNotApplyFilterOnInvalidFilterable(
        $filterable,
        string $expectedMessage
    ): void {
        $filter = new FilterWithOperator('any', new Value('filter'), 'any', 'any');

        $this->expectException(ApiFilterExceptionInterface::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->apiFilter->applyFilter($filter, $filterable);
    }

    public function provideNotSupportedFilterable(): array
    {
        return [
            // filterable, errorMessage
            'simple SQL' => [
                'SELECT * FROM table',
                'Unsupported filterable of type "string".',
            ],
            'array' => [
                [],
                'Unsupported filterable of type "array".',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provideNotSupportedFilterable
     *
     * @param mixed $filterable of type <T>
     */
    public function shouldNotApplyFiltersToUnsupportedFilterable(
        $filterable,
        string $expectedMessage
    ): void {
        $filters = Filters::from([new FilterWithOperator('col', new Value('val'), '=', 'eq')]);

        $this->expectException(ApiFilterExceptionInterface::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->apiFilter->applyFilters($filters, $filterable);
    }

    /**
     * @test
     * @dataProvider provideNotSupportedFilterable
     *
     * @param mixed $filterable of type <T>
     */
    public function shouldNotPrepareValueForInvalidFilterable(
        $filterable,
        string $expectedMessage
    ): void {
        $this->expectException(ApiFilterExceptionInterface::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->apiFilter->getPreparedValue(new FilterWithOperator('col', new Value('val'), '=', 'eq'), $filterable);
    }

    /**
     * @test
     * @dataProvider provideNotSupportedFilterable
     *
     * @param mixed $filterable of type <T>
     */
    public function shouldNotPrepareValuesForInvalidFilterable(
        $filterable,
        string $expectedMessage
    ): void {
        $filters = Filters::from([new FilterWithOperator('col', new Value('val'), '=', 'eq')]);

        $this->expectException(ApiFilterExceptionInterface::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->apiFilter->getPreparedValues($filters, $filterable);
    }
}
