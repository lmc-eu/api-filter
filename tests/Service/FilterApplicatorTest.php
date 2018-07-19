<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Lmc\ApiFilter\AbstractTestCase;
use Lmc\ApiFilter\Applicator\ApplicatorInterface;
use Lmc\ApiFilter\Applicator\ApplicatorSql;
use Lmc\ApiFilter\Applicator\DoctrineQueryBuilderApplicator;
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
        $this->filterApplicator = new FilterApplicator(
            new EscapeService()
        );
    }

    /**
     * @test
     * @dataProvider provideFilter
     */
    public function shouldApplyFilter(
        ApplicatorInterface $applicator,
        FilterInterface $filter,
        string $filterable,
        string $expected
    ): void {
        if ($applicator instanceof DoctrineQueryBuilderApplicator) {
            $this->markTestIncomplete('Todo - finish with imlementing apply method on QueryBuilderApplicator');
        }
        $this->filterApplicator->registerApplicator($applicator, 1);

        $result = $this->filterApplicator->apply($filter, new Filterable($filterable))->getValue();

        $this->assertSame($expected, $result);
    }

    public function provideFilter(): array
    {
        return [
            // applicator, filter, filterable, expected
            'sql - eq' => [
                new ApplicatorSql(),
                new FilterWithOperator('col', new Value('val'), '='),
                'SELECT * FROM table WHERE public = 1',
                'SELECT * FROM table WHERE public = 1 AND col = val',
            ],
            'sql - gt' => [
                new ApplicatorSql(),
                new FilterWithOperator('col', new Value('val'), '>'),
                'SELECT * FROM table WHERE public = 1',
                'SELECT * FROM table WHERE public = 1 AND col > val',
            ],
            'sql - gte' => [
                new ApplicatorSql(),
                new FilterWithOperator('col', new Value(10), '>='),
                'SELECT * FROM table WHERE public = 1',
                'SELECT * FROM table WHERE public = 1 AND col >= 10',
            ],
            'queryBuilder - eq' => [
                new DoctrineQueryBuilderApplicator(),
                new FilterWithOperator('col', new Value('val'), '='),
                'SELECT * FROM table WHERE public = 1',
                'SELECT * FROM table WHERE public = 1 AND col = val',
            ],
            'queryBuilder - gt' => [
                new DoctrineQueryBuilderApplicator(),
                new FilterWithOperator('col', new Value('val'), '>'),
                'SELECT * FROM table WHERE public = 1',
                'SELECT * FROM table WHERE public = 1 AND col > val',
            ],
            'queryBuilder - gte' => [
                new DoctrineQueryBuilderApplicator(),
                new FilterWithOperator('col', new Value(10), '>='),
                'SELECT * FROM table WHERE public = 1',
                'SELECT * FROM table WHERE public = 1 AND col >= 10',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provideFilters
     */
    public function shouldApplyAllFilters(
        ApplicatorInterface $applicator,
        array $filters,
        string $filterable,
        string $expected
    ): void {
        if ($applicator instanceof DoctrineQueryBuilderApplicator) {
            $this->markTestIncomplete('Todo - finish with imlementing apply method on QueryBuilderApplicator');
        }
        $this->filterApplicator->registerApplicator($applicator, 1);

        $result = $this->filterApplicator->applyAll(Filters::from($filters), new Filterable($filterable))->getValue();

        $this->assertSame($expected, $result);
    }

    public function provideFilters(): array
    {
        return [
            // applicator, filters, filterable, expected
            'sql - between' => [
                new ApplicatorSql(),
                [
                    new FilterWithOperator('column', new Value('max'), '>'),
                    new FilterWithOperator('column', new Value('min'), '<'),
                ],
                'SELECT * FROM table',
                'SELECT * FROM table WHERE 1 AND column > max AND column < min',
            ],
            'queryBuilder - between' => [
                new DoctrineQueryBuilderApplicator(),
                [
                    new FilterWithOperator('column', new Value('max'), '>'),
                    new FilterWithOperator('column', new Value('min'), '<'),
                ],
                'SELECT * FROM table',
                'SELECT * FROM table WHERE 1 AND column > max AND column < min',
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
        $filter = new FilterWithOperator('any', new Value('filter'), 'any');

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
