<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Applicator;

use Lmc\ApiFilter\AbstractTestCase;
use Lmc\ApiFilter\Entity\Filterable;
use Lmc\ApiFilter\Entity\ParameterDefinition;
use Lmc\ApiFilter\Entity\Value;
use Lmc\ApiFilter\Filter\FilterFunction;
use Lmc\ApiFilter\Filter\FilterWithOperator;
use Lmc\ApiFilter\Filter\FunctionParameter;

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

    /**
     * @test
     */
    public function shouldApplyFunction(): void
    {
        $filterable = new Filterable('SELECT * FROM person');

        $filter = new FilterFunction(
            'fullName',
            new Value(function ($filterable, FunctionParameter $firstName, FunctionParameter $surname) {
                $filterable = new Filterable($filterable);
                $filterable = $this->sqlApplicator->applyFilterWithOperator(
                    new FilterWithOperator($firstName->getColumn(), $firstName->getValue(), '=', 'fun'),
                    $filterable
                );

                $filterable = $this->sqlApplicator->applyFilterWithOperator(
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

        $result = $this->sqlApplicator->applyFilterFunction($filter, $filterable, $parameters);
        $preparedValues = $this->sqlApplicator->getPreparedValuesForFunction($parameters);

        $this->assertSame(
            'SELECT * FROM person WHERE 1 AND firstName = :firstName_fun AND surname = :surname_fun',
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

        $preparedValues = $this->sqlApplicator->getPreparedValuesForFunction($parameters, $parameterDefinition);

        $this->assertSame(['firstName_function_parameter' => 'Jon'], $preparedValues);
    }
}
