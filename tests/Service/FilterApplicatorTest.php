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
use Lmc\ApiFilter\Exception\InvalidArgumentException;
use Lmc\ApiFilter\Filter\FilterFunction;
use Lmc\ApiFilter\Filter\FilterIn;
use Lmc\ApiFilter\Filter\FilterWithOperator;
use Lmc\ApiFilter\Filter\FunctionParameter;
use Lmc\ApiFilter\Filters\Filters;
use Mockery as m;

class FilterApplicatorTest extends AbstractTestCase
{
    private FilterApplicator $filterApplicator;
    private Functions $functions;

    protected function setUp(): void
    {
        $this->functions = new Functions();
        $this->filterApplicator = new FilterApplicator($this->functions);
    }

    /**
     * @test
     * @dataProvider provideFilter
     *
     * @param mixed $filterable of type <T>
     * @param mixed $expected of type <T>
     */
    public function shouldApplyFilter(
        ApplicatorInterface $applicator,
        array $filters,
        $filterable,
        $expected,
        array $expectedPreparedValue,
        array $functionsToRegister = []
    ): void {
        foreach ($functionsToRegister as $function) {
            $function[] = $this->createDummyCallback('function in apply filter');
            $this->functions->register(...$function);
        }

        [$filter] = $filters;
        $this->filterApplicator->setFilters(Filters::from($filters));

        $filterable = new Filterable($filterable);
        $this->filterApplicator->registerApplicator($applicator, 1);

        $result = $this->filterApplicator->apply($filter, $filterable)->getValue();
        $preparedValue = $this->filterApplicator->getPreparedValue($filter, $filterable);

        $this->assertEquals($expected, $result);
        $this->assertSame($expectedPreparedValue, $preparedValue);
    }

    public function provideFilter(): array
    {
        $sqlApplicator = new SqlApplicator();
        $queryBuilderApplicator = new QueryBuilderApplicator();

        $fullName = function (ApplicatorInterface $applicator) {
            return function ($filterable, FunctionParameter $firstName, FunctionParameter $surname) use ($applicator) {
                $filterable = new Filterable($filterable);
                $filterable = $applicator->applyFilterWithOperator(
                    new FilterWithOperator(
                        $firstName->getColumn(),
                        $firstName->getValue(),
                        '=',
                        'function_parameter'
                    ),
                    $filterable
                );

                $filterable = $applicator->applyFilterWithOperator(
                    new FilterWithOperator($surname->getColumn(), $surname->getValue(), '=', 'function_parameter'),
                    $filterable
                );

                return $filterable->getValue();
            };
        };

        return [
            // applicator, filters, filterable, expected, expected prepared values, functions to register
            'sql - eq' => [
                $sqlApplicator,
                [new FilterWithOperator('col', new Value('val'), '=', 'eq')],
                'SELECT * FROM table WHERE public = 1',
                'SELECT * FROM table WHERE public = 1 AND col = :col_eq',
                ['col_eq' => 'val'],
            ],
            'sql - neq' => [
                $sqlApplicator,
                [new FilterWithOperator('col', new Value('val'), '!=', 'neq')],
                'SELECT * FROM table WHERE public != 1',
                'SELECT * FROM table WHERE public != 1 AND col != :col_neq',
                ['col_neq' => 'val'],
            ],
            'sql - gt' => [
                $sqlApplicator,
                [new FilterWithOperator('col', new Value('val'), '>', 'gt')],
                'SELECT * FROM table WHERE public = 1',
                'SELECT * FROM table WHERE public = 1 AND col > :col_gt',
                ['col_gt' => 'val'],
            ],
            'sql - gte' => [
                $sqlApplicator,
                [new FilterWithOperator('col', new Value(10), '>=', 'gte')],
                'SELECT * FROM table WHERE public = 1',
                'SELECT * FROM table WHERE public = 1 AND col >= :col_gte',
                ['col_gte' => 10],
            ],
            'sql - in' => [
                $sqlApplicator,
                [new FilterIn('col', new Value([1, 2]))],
                'SELECT * FROM table WHERE public = 1',
                'SELECT * FROM table WHERE public = 1 AND col IN (:col_in_0, :col_in_1)',
                ['col_in_0' => 1, 'col_in_1' => 2],
            ],
            'sql - function' => [
                $sqlApplicator,
                [
                    new FilterFunction('fullName', new Value($fullName($sqlApplicator))),
                    new FunctionParameter('firstName', new Value('Jon')),
                    new FunctionParameter('surname', new Value('Snow')),
                ],
                'SELECT * FROM table WHERE public = 1',
                'SELECT * FROM table WHERE public = 1 AND firstName = :firstName_function_parameter AND surname = :surname_function_parameter',
                ['firstName_function_parameter' => 'Jon', 'surname_function_parameter' => 'Snow'],
                [
                    ['fullName', ['firstName', 'surname']],
                ],
            ],
            'queryBuilder - eq' => [
                $queryBuilderApplicator,
                [new FilterWithOperator('col', new Value('val'), '=', 'eq')],
                $this->setUpQueryBuilder(),
                $this->setUpQueryBuilder()->andWhere('t.col = :col_eq'),
                ['col_eq' => 'val'],
            ],
            'queryBuilder - gt' => [
                $queryBuilderApplicator,
                [new FilterWithOperator('col', new Value('val'), '>', 'gt')],
                $this->setUpQueryBuilder(),
                $this->setUpQueryBuilder()->andWhere('t.col > :col_gt'),
                ['col_gt' => 'val'],
            ],
            'queryBuilder - gte' => [
                $queryBuilderApplicator,
                [new FilterWithOperator('col', new Value(10), '>=', 'gte')],
                $this->setUpQueryBuilder(),
                $this->setUpQueryBuilder()->andWhere('t.col >= :col_gte'),
                ['col_gte' => 10],
            ],
            'queryBuilder - in' => [
                $queryBuilderApplicator,
                [new FilterIn('col', new Value([1, 2]))],
                $this->setUpQueryBuilder(),
                $this->setUpQueryBuilder()->andWhere('t.col IN (:col_in)'),
                ['col_in' => [1, 2]],
            ],
            'queryBuilder - function' => [
                $queryBuilderApplicator,
                [
                    new FilterFunction('fullName', new Value($fullName($queryBuilderApplicator))),
                    new FunctionParameter('firstName', new Value('Jon')),
                    new FunctionParameter('surname', new Value('Snow')),
                ],
                $this->setUpQueryBuilder(),
                $this->setUpQueryBuilder()->andWhere('t.firstName = :firstName_function_parameter')->andWhere('t.surname = :surname_function_parameter'),
                ['firstName_function_parameter' => 'Jon', 'surname_function_parameter' => 'Snow'],
                [
                    ['fullName', ['firstName', 'surname']],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provideFilters
     *
     * @param mixed $filterable of type <T>
     * @param mixed $expected of type <T>
     */
    public function shouldApplyAllFilters(
        ApplicatorInterface $applicator,
        array $filters,
        $filterable,
        $expected,
        array $expectedPreparedValues,
        array $functionsToRegister = []
    ): void {
        foreach ($functionsToRegister as $function) {
            $function[] = $this->createDummyCallback('function in apply filters');
            $this->functions->register(...$function);
        }

        $filters = Filters::from($filters);
        $filterable = new Filterable($filterable);
        $this->filterApplicator->setFilters($filters);
        $this->filterApplicator->registerApplicator($applicator, 1);

        $result = $this->filterApplicator->applyAll($filters, $filterable)->getValue();
        $preparedValues = $this->filterApplicator->getPreparedValues($filters, $filterable);

        $this->assertEquals($expected, $result);
        $this->assertSame($expectedPreparedValues, $preparedValues);
    }

    public function provideFilters(): array
    {
        $sqlApplicator = new SqlApplicator();
        $queryBuilderApplicator = new QueryBuilderApplicator();

        $fullName = function (ApplicatorInterface $applicator) {
            return function ($filterable, FunctionParameter $firstName, FunctionParameter $surname) use ($applicator) {
                $filterable = new Filterable($filterable);
                $filterable = $applicator->applyFilterWithOperator(
                    new FilterWithOperator(
                        $firstName->getColumn(),
                        $firstName->getValue(),
                        '=',
                        'function_parameter'
                    ),
                    $filterable
                );

                $filterable = $applicator->applyFilterWithOperator(
                    new FilterWithOperator($surname->getColumn(), $surname->getValue(), '=', 'function_parameter'),
                    $filterable
                );

                return $filterable->getValue();
            };
        };

        return [
            // applicator, filters, filterable, expected
            'sql - between' => [
                $sqlApplicator,
                [
                    new FilterWithOperator('column', new Value('min'), '>', 'gt'),
                    new FilterWithOperator('column', new Value('max'), '<', 'lt'),
                ],
                'SELECT * FROM table',
                'SELECT * FROM table WHERE 1 AND column > :column_gt AND column < :column_lt',
                ['column_gt' => 'min', 'column_lt' => 'max'],
            ],
            'sql - eq + in' => [
                $sqlApplicator,
                [
                    new FilterWithOperator('allowed', new Value('true'), '=', 'eq'),
                    new FilterIn('color', new Value(['red', 'blue'])),
                ],
                'SELECT * FROM table',
                'SELECT * FROM table WHERE 1 AND allowed = :allowed_eq AND color IN (:color_in_0, :color_in_1)',
                ['allowed_eq' => 'true', 'color_in_0' => 'red', 'color_in_1' => 'blue'],
            ],
            'sql - eq + in + function' => [
                $sqlApplicator,
                [
                    new FilterWithOperator('allowed', new Value('true'), '=', 'eq'),
                    new FilterIn('color', new Value(['red', 'blue'])),
                    new FilterFunction('fullName', new Value($fullName($sqlApplicator))),
                    new FunctionParameter('firstName', new Value('Jon')),
                    new FunctionParameter('surname', new Value('Snow')),
                ],
                'SELECT * FROM table',
                'SELECT * FROM table WHERE 1 ' .
                'AND allowed = :allowed_eq ' .
                'AND color IN (:color_in_0, :color_in_1) ' .
                'AND firstName = :firstName_function_parameter AND surname = :surname_function_parameter',
                [
                    'allowed_eq' => 'true',
                    'color_in_0' => 'red',
                    'color_in_1' => 'blue',
                    'firstName_function_parameter' => 'Jon',
                    'surname_function_parameter' => 'Snow',
                ],
                [
                    ['fullName', ['firstName', 'surname']],
                ],
            ],
            'queryBuilder - between' => [
                $queryBuilderApplicator,
                [
                    new FilterWithOperator('column', new Value('min'), '>', 'gt'),
                    new FilterWithOperator('column', new Value('max'), '<', 'lt'),
                ],
                $this->setUpQueryBuilder(),
                $this->setUpQueryBuilder()->andWhere('t.column > :column_gt')->andWhere('t.column < :column_lt'),
                ['column_gt' => 'min', 'column_lt' => 'max'],
            ],
            'queryBuilder - eq + in' => [
                $queryBuilderApplicator,
                [
                    new FilterWithOperator('allowed', new Value('true'), '=', 'eq'),
                    new FilterIn('color', new Value(['red', 'blue'])),
                ],
                $this->setUpQueryBuilder(),
                $this->setUpQueryBuilder()->andWhere('t.allowed = :allowed_eq')->andWhere('t.color IN (:color_in)'),
                ['allowed_eq' => 'true', 'color_in' => ['red', 'blue']],
            ],
            'queryBuilder - eq + in + function' => [
                $queryBuilderApplicator,
                [
                    new FilterWithOperator('allowed', new Value('true'), '=', 'eq'),
                    new FilterIn('color', new Value(['red', 'blue'])),
                    new FilterFunction('fullName', new Value($fullName($queryBuilderApplicator))),
                    new FunctionParameter('firstName', new Value('Jon')),
                    new FunctionParameter('surname', new Value('Snow')),
                ],
                $this->setUpQueryBuilder(),
                $this->setUpQueryBuilder()
                    ->andWhere('t.allowed = :allowed_eq')
                    ->andWhere('t.color IN (:color_in)')
                    ->andWhere('t.firstName = :firstName_function_parameter')
                    ->andWhere('t.surname = :surname_function_parameter'),
                [
                    'allowed_eq' => 'true',
                    'color_in' => ['red', 'blue'],
                    'firstName_function_parameter' => 'Jon',
                    'surname_function_parameter' => 'Snow',
                ],
                [
                    ['fullName', ['firstName', 'surname']],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provideNotSupportedFilterable
     *
     * @param mixed $filterableInput of type <T>
     */
    public function shouldThrowInvalidArgumentExceptionOnApplyFilterOnNotSupportedFilterable(
        $filterableInput,
        string $expectedMessage
    ): void {
        $filterable = new Filterable($filterableInput);
        $filter = new FilterWithOperator('any', new Value('filter'), 'any', 'any');

        $this->filterApplicator->setFilters(Filters::from([$filter]));

        $this->expectException(InvalidArgumentException::class);
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

    /**
     * @test
     */
    public function shouldNotApplyFilterFunctionWithoutAllParameters(): void
    {
        $fullName = $this->createDummyCallback('fullName');
        $this->functions->register('fullName', ['firstName', 'surname'], $fullName);

        $filterable = new Filterable('');
        $this->filterApplicator->registerApplicator(new SqlApplicator(), 1);
        $this->filterApplicator->setFilters(new Filters());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Function parameter "firstName" is missing.');

        $this->filterApplicator->apply(new FilterFunction('fullName', new Value($fullName)), $filterable);
    }

    /**
     * @test
     */
    public function shouldNotApplyFilterWithoutAllFilters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Filters must be set before applying.');

        $this->filterApplicator->apply(new FilterWithOperator('col', new Value('val'), '=', 'eq'), new Filterable(''));
    }

    /**
     * @test
     */
    public function shouldNotApplyFiltersWithoutAllFilters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Filters must be set before applying.');

        $this->filterApplicator->applyAll(
            Filters::from([new FilterWithOperator('col', new Value('val'), '=', 'eq')]),
            new Filterable('')
        );
    }
}
