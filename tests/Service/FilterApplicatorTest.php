<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Lmc\ApiFilter\AbstractTestCase;
use Lmc\ApiFilter\Applicator\ApplicatorInterface;
use Lmc\ApiFilter\Applicator\QueryBuilderApplicator;
use Lmc\ApiFilter\Applicator\SqlApplicator;
use Lmc\ApiFilter\Entity\Filterable;
use Lmc\ApiFilter\Entity\Value;
use Lmc\ApiFilter\Filter\FilterInterface;
use Lmc\ApiFilter\Filter\FilterWithOperator;
use Lmc\ApiFilter\Filters\Filters;
use Mockery as m;

class FilterApplicatorTest extends AbstractTestCase
{
    /** @var FilterApplicator */
    private $filterApplicator;

    protected function setUp(): void
    {
        $this->filterApplicator = new FilterApplicator();
    }

    /**
     * @test
     * @dataProvider provideFilter
     *
     * @param mixed $filterable
     * @param mixed $expected
     */
    public function shouldApplyFilter(
        ApplicatorInterface $applicator,
        FilterInterface $filter,
        $filterable,
        $expected,
        array $expectedPreparedValue
    ): void {
        $filterable = new Filterable($filterable);
        $this->filterApplicator->registerApplicator($applicator, 1);

        $result = $this->filterApplicator->apply($filter, $filterable)->getValue();
        $preparedValue = $this->filterApplicator->getPreparedValue($filter, $filterable);

        $this->assertEquals($expected, $result);
        $this->assertSame($expectedPreparedValue, $preparedValue);
    }

    public function provideFilter(): array
    {
        return [
            // applicator, filter, filterable, expected, expected prepared values
            'sql - eq' => [
                new SqlApplicator(),
                new FilterWithOperator('col', new Value('val'), '=', 'eq'),
                'SELECT * FROM table WHERE public = 1',
                'SELECT * FROM table WHERE public = 1 AND col = :col_eq',
                ['col_eq' => 'val'],
            ],
            'sql - gt' => [
                new SqlApplicator(),
                new FilterWithOperator('col', new Value('val'), '>', 'gt'),
                'SELECT * FROM table WHERE public = 1',
                'SELECT * FROM table WHERE public = 1 AND col > :col_gt',
                ['col_gt' => 'val'],
            ],
            'sql - gte' => [
                new SqlApplicator(),
                new FilterWithOperator('col', new Value(10), '>=', 'gte'),
                'SELECT * FROM table WHERE public = 1',
                'SELECT * FROM table WHERE public = 1 AND col >= :col_gte',
                ['col_gte' => 10],
            ],
            'queryBuilder - eq' => [
                new QueryBuilderApplicator(),
                new FilterWithOperator('col', new Value('val'), '=', 'eq'),
                $this->setUpQueryBuilder(),
                $this->setUpQueryBuilder()->andWhere('t.col = :col_eq'),
                ['col_eq' => 'val'],
            ],
            'queryBuilder - gt' => [
                new QueryBuilderApplicator(),
                new FilterWithOperator('col', new Value('val'), '>', 'gt'),
                $this->setUpQueryBuilder(),
                $this->setUpQueryBuilder()->andWhere('t.col > :col_gt'),
                ['col_gt' => 'val'],
            ],
            'queryBuilder - gte' => [
                new QueryBuilderApplicator(),
                new FilterWithOperator('col', new Value(10), '>=', 'gte'),
                $this->setUpQueryBuilder(),
                $this->setUpQueryBuilder()->andWhere('t.col >= :col_gte'),
                ['col_gte' => 10],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provideFilters
     *
     * @param mixed $filterable
     * @param mixed $expected
     */
    public function shouldApplyAllFilters(
        ApplicatorInterface $applicator,
        array $filters,
        $filterable,
        $expected,
        array $expectedPreparedValues
    ): void {
        $filters = Filters::from($filters);
        $filterable = new Filterable($filterable);
        $this->filterApplicator->registerApplicator($applicator, 1);

        $result = $this->filterApplicator->applyAll($filters, $filterable)->getValue();
        $preparedValues = $this->filterApplicator->getPreparedValues($filters, $filterable);

        $this->assertEquals($expected, $result);
        $this->assertSame($expectedPreparedValues, $preparedValues);
    }

    public function provideFilters(): array
    {
        return [
            // applicator, filters, filterable, expected
            'sql - between' => [
                new SqlApplicator(),
                [
                    new FilterWithOperator('column', new Value('min'), '>', 'gt'),
                    new FilterWithOperator('column', new Value('max'), '<', 'lt'),
                ],
                'SELECT * FROM table',
                'SELECT * FROM table WHERE 1 AND column > :column_gt AND column < :column_lt',
                ['column_gt' => 'min', 'column_lt' => 'max'],
            ],
            'queryBuilder - between' => [
                new QueryBuilderApplicator(),
                [
                    new FilterWithOperator('column', new Value('min'), '>', 'gt'),
                    new FilterWithOperator('column', new Value('max'), '<', 'lt'),
                ],
                $this->setUpQueryBuilder(),
                $this->setUpQueryBuilder()->andWhere('t.column > :column_gt')->andWhere('t.column < :column_lt'),
                ['column_gt' => 'min', 'column_lt' => 'max'],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provideNotSupportedFilterable
     *
     * @param mixed $filterableInput
     */
    public function shouldThrowInvalidArgumentExceptionOnApplyFilterOnNotSupportedFilterable(
        $filterableInput,
        string $expectedMessage
    ): void {
        $filterable = new Filterable($filterableInput);
        $filter = new FilterWithOperator('any', new Value('filter'), 'any', 'any');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->filterApplicator->apply($filter, $filterable);
    }

    public function provideNotSupportedFilterable(): array
    {
        return [
            // filterable, errorMessage
            'string' => [
                'string filterable',
                'Unsupported filterable of type "string".',
            ],
            'queryBuilder' => [
                new QueryBuilder(m::mock(EntityManagerInterface::class)),
                'Unsupported filterable of type "Doctrine\ORM\QueryBuilder".',
            ],
        ];
    }
}
