<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Applicator;

use Doctrine\ORM\QueryBuilder;
use Lmc\ApiFilter\AbstractTestCase;
use Lmc\ApiFilter\Entity\Filterable;
use Lmc\ApiFilter\Entity\ParameterDefinition;
use Lmc\ApiFilter\Entity\Value;
use Lmc\ApiFilter\Filter\FilterFunction;
use Lmc\ApiFilter\Filter\FilterWithOperator;
use Lmc\ApiFilter\Filter\FunctionParameter;

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

    /**
     * @test
     */
    public function shouldApplyFunction(): void
    {
        $filterable = new Filterable($this->queryBuilder);

        $filter = new FilterFunction(
            'fullName',
            new Value(function ($filterable, FunctionParameter $firstName, FunctionParameter $surname) {
                $filterable = new Filterable($filterable);
                $filterable = $this->queryBuilderApplicator->applyFilterWithOperator(
                    new FilterWithOperator(
                        $firstName->getColumn(),
                        $firstName->getValue(),
                        '=',
                        'fun'
                    ),
                    $filterable
                );

                $filterable = $this->queryBuilderApplicator->applyFilterWithOperator(
                    new FilterWithOperator($surname->getColumn(), $surname->getValue(), '=', 'fun'),
                    $filterable
                );

                return $filterable->getValue();
            })
        );
        $parameters = [
            new FunctionParameter('firstName', new Value('Jon')),
            new FunctionParameter('surname', new Value('Snow')),
        ];

        $result = $this->queryBuilderApplicator->applyFilterFunction($filter, $filterable, $parameters);
        $preparedValues = $this->queryBuilderApplicator->getPreparedValuesForFunction($parameters);

        $this->assertDqlWhere(
            ['t.firstName = :firstName_fun', 't.surname = :surname_fun'],
            $result->getValue()
        );
        $this->assertSame(
            ['firstName_function_parameter' => 'Jon', 'surname_function_parameter' => 'Snow'],
            $preparedValues
        );
    }

    /**
     * @test
     */
    public function shouldPrepareFunctionParametersWithDefinition(): void
    {
        $parameterDefinition = [
            ParameterDefinition::equalToDefaultValue('firstName', new Value('Jon')),
        ];

        $parameters = [
            new FunctionParameter('firstName', new Value('overridden by default value')),
            new FunctionParameter('Not defined', new Value('doesnt matter')),
        ];

        $preparedValues = $this->queryBuilderApplicator->getPreparedValuesForFunction(
            $parameters,
            $parameterDefinition
        );

        $this->assertSame(['firstName_function_parameter' => 'Jon'], $preparedValues);
    }
}
