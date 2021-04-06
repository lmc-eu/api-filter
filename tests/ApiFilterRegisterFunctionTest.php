<?php declare(strict_types=1);

namespace Lmc\ApiFilter;

use Doctrine\ORM\QueryBuilder;
use Lmc\ApiFilter\Applicator\SqlApplicator;
use Lmc\ApiFilter\Constant\Priority;
use Lmc\ApiFilter\Entity\ParameterDefinition;
use Lmc\ApiFilter\Entity\Value;
use Lmc\ApiFilter\Exception\ApiFilterExceptionInterface;
use Lmc\ApiFilter\Filter\FunctionParameter;
use Lmc\ApiFilter\Fixture\SimpleClient;

class ApiFilterRegisterFunctionTest extends AbstractTestCase
{
    private ApiFilter $apiFilter;
    private QueryBuilder $queryBuilder;

    protected function setUp(): void
    {
        $this->queryBuilder = $this->setUpQueryBuilder();

        $this->apiFilter = new ApiFilter();
        $this->apiFilter->registerApplicator(new SqlApplicator(), Priority::HIGHEST);
    }

    /**
     * @test
     * @dataProvider provideDeclareFunction
     */
    public function shouldDeclareFunction(
        string $functionName,
        array $parameters,
        array $queryParameters,
        array $expectedDql,
        array $expectedPreparedValues
    ): void {
        [$queryBuilderWithFilters, $preparedValues] = $this->apiFilter
            ->declareFunction($functionName, $parameters)
            ->applyFunction($functionName, $queryParameters, $this->queryBuilder);

        $this->assertInstanceOf(QueryBuilder::class, $queryBuilderWithFilters);
        $this->assertDqlWhere($expectedDql, $queryBuilderWithFilters);
        $this->assertSame($expectedPreparedValues, $preparedValues);
    }

    public function provideDeclareFunction(): array
    {
        return [
            // functionName, parameters, queryParameters, expectedDQL, expectedPreparedValues
            'implicit equals' => [
                'fullName',
                ['firstName', new ParameterDefinition('surname')],
                ['fullName' => '(Jon,Snow)'],
                ['t.firstName = :firstName_function_parameter', 't.surname = :surname_function_parameter'],
                ['firstName_function_parameter' => 'Jon', 'surname_function_parameter' => 'Snow'],
            ],
            'explicit equals' => [
                'fullName',
                [['firstName', 'eq'], new ParameterDefinition('surname', 'eq')],
                ['fullName' => '(Jon,Snow)'],
                ['t.firstName = :firstName_function_parameter', 't.surname = :surname_function_parameter'],
                ['firstName_function_parameter' => 'Jon', 'surname_function_parameter' => 'Snow'],
            ],
            'explicit between (with mapping to column)' => [
                'inAge',
                [['ageFrom', 'gt', 'age'], new ParameterDefinition('ageTo', 'lt', 'age')],
                ['inAge' => '(18,30)'],
                ['t.age > :ageFrom_function_parameter', 't.age < :ageTo_function_parameter'],
                ['ageFrom_function_parameter' => 18, 'ageTo_function_parameter' => 30],
            ],
            'explicit with defaults' => [
                'girlInAge',
                [['ageFrom', 'gt', 'age'], ['ageTo', 'lt', 'age'], ['gender', 'eq', 'gender', 'female']],
                ['girlInAge' => '(18,30)'],
                [
                    't.age > :ageFrom_function_parameter',
                    't.age < :ageTo_function_parameter',
                    't.gender = :gender_function_parameter',
                ],
                [
                    'ageFrom_function_parameter' => 18,
                    'ageTo_function_parameter' => 30,
                    'gender_function_parameter' => 'female',
                ],
            ],
            'explicit with defaults - by parameters' => [
                'girlInAge',
                [
                    new ParameterDefinition('ageFrom', 'gt', 'age'),
                    new ParameterDefinition('ageTo', 'lt', 'age'),
                    new ParameterDefinition('gender', null, null, new Value('female')),
                ],
                ['girlInAge' => '(18,30)'],
                [
                    't.age > :ageFrom_function_parameter',
                    't.age < :ageTo_function_parameter',
                    't.gender = :gender_function_parameter',
                ],
                [
                    'ageFrom_function_parameter' => 18,
                    'ageTo_function_parameter' => 30,
                    'gender_function_parameter' => 'female',
                ],
            ],
            'explicit with defaults - combined all possible declarations' => [
                'girlInAge',
                [
                    ['ageFrom', 'gt', 'age'],
                    new ParameterDefinition('ageTo', 'lt', 'age'),
                    ParameterDefinition::equalToDefaultValue('gender', new Value('female')),
                ],
                ['girlInAge' => '(18,30)'],
                [
                    't.age > :ageFrom_function_parameter',
                    't.age < :ageTo_function_parameter',
                    't.gender = :gender_function_parameter',
                ],
                [
                    'ageFrom_function_parameter' => 18,
                    'ageTo_function_parameter' => 30,
                    'gender_function_parameter' => 'female',
                ],
            ],
            'explicit with defaults - combined all possible declarations - and use default value' => [
                'girlInAge',
                [
                    ['ageFrom', 'gt', 'age'],
                    new ParameterDefinition('ageTo', 'lt', 'age'),
                    ParameterDefinition::equalToDefaultValue('gender', new Value('female')),
                ],
                ['girlInAge' => '(18,30)'],
                [
                    't.age > :ageFrom_function_parameter',
                    't.age < :ageTo_function_parameter',
                    't.gender = :gender_function_parameter',
                ],
                [
                    'ageFrom_function_parameter' => 18,
                    'ageTo_function_parameter' => 30,
                    'gender_function_parameter' => 'female',
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provideMultipleFunctionsQueryParameters
     */
    public function shouldDeclareAndExecuteMultipleFunctionsAndOtherFilters(array $queryParameters): void
    {
        $expectedDqlWhere = [
            't.age > :ageFrom_function_parameter',
            't.age < :ageTo_function_parameter',
            't.size IN (:size_function_parameter)',
            't.firstName = :firstName_eq',
            't.zone = :zone_function_parameter',
            't.bucket = :bucket_function_parameter',
        ];
        $expectedPreparedValues = [
            'ageFrom_function_parameter' => 18,
            'ageTo_function_parameter' => 30,
            'size_function_parameter' => ['A4', 'A5'],
            'firstName_eq' => 'Foo',
            'zone_function_parameter' => 'all',
            'bucket_function_parameter' => 'common',
        ];

        $this->apiFilter
            ->declareFunction(
                'perfectBook',
                [
                    ['ageFrom', 'gt', 'age'],
                    ['ageTo', 'lt', 'age'],
                    ['size', 'in'],
                ]
            )
            ->declareFunction('spot', ['zone', 'bucket']);

        $filters = $this->apiFilter->parseFilters($queryParameters);

        $queryBuilderWithFilters = $this->apiFilter->applyFilters($filters, $this->queryBuilder);
        $preparedValues = $this->apiFilter->getPreparedValues($filters, $this->queryBuilder);

        if (!empty($queryParameters)) {
            // application of filters should not change original query builder
            $this->assertNotSame($this->queryBuilder, $queryBuilderWithFilters);
        }

        $this->assertInstanceOf(QueryBuilder::class, $queryBuilderWithFilters);
        $this->assertDqlWhere($expectedDqlWhere, $queryBuilderWithFilters);
        $this->assertSameValues($expectedPreparedValues, $preparedValues);
    }

    public function provideMultipleFunctionsQueryParameters(): array
    {
        return [
            // queryParameters
            'functions' => [
                [
                    'perfectBook' => '(18, 30, [A4; A5])',
                    'firstName' => 'Foo',
                    'spot' => '(all,common)',
                ],
            ],
            'implicit - tuples (with different order of parameters in tuple)' => [
                [
                    '(ageTo,ageFrom,size)' => '(30, 18, [A4; A5])',
                    'firstName' => 'Foo',
                    '(zone,bucket)' => '(all,common)',
                ],
            ],
            'explicit - tuples' => [
                [
                    '(function,ageFrom,ageTo,size)' => '(perfectBook, 18, 30, [A4; A5])',
                    'firstName' => 'Foo',
                    '(function,zone,bucket)' => '(spot, all, common)',
                ],
            ],
            'implicit - values' => [
                [
                    'firstName' => 'Foo',
                    'bucket' => 'common',
                    'ageFrom' => 18,
                    'ageTo' => 30,
                    'zone' => 'all',
                    'size' => ['A4', 'A5'],
                ],
            ],
            'explicit - values' => [
                [
                    'function' => ['perfectBook', 'spot'],
                    'firstName' => 'Foo',
                    'bucket' => 'common',
                    'ageFrom' => 18,
                    'ageTo' => 30,
                    'zone' => 'all',
                    'size' => ['A4', 'A5'],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provideFullNameQueryParameters
     */
    public function shouldDeclareAndExecuteFunctionWhichUsesApiFilter(array $queryParameters): void
    {
        $sql = 'SELECT * FROM person';
        $expectedSql = 'SELECT * FROM person WHERE 1 AND firstName = :firstName_function_parameter AND surname = :surname_function_parameter';
        $expectedPreparedValues = ['firstName_function_parameter' => 'Jon', 'surname_function_parameter' => 'Snow'];

        [$appliedSql, $preparedValues] = $this->apiFilter
            ->declareFunction('fullName', ['firstName', 'surname'])
            ->applyFunction('fullName', $queryParameters, $sql);

        $this->assertSame($expectedSql, $appliedSql);
        $this->assertSame($expectedPreparedValues, $preparedValues);
    }

    public function provideFullNameQueryParameters(): array
    {
        return [
            // queryParameters
            'function' => [['fullName' => '(Jon,Snow)']],
            'implicit - tuple' => [['(firstName,surname)' => '(Jon,Snow)']],
            'implicit - single values' => [['firstName' => 'Jon', 'surname' => 'Snow']],
            'explicit - tuple' => [['(function,firstName,surname)' => '(fullName,Jon,Snow)']],
            'explicit - single values' => [['function' => ['fullName'], 'firstName' => 'Jon', 'surname' => 'Snow']],
            'explicit - filter' => [['filter' => ['(fullName, Jon, Snow)']]],
        ];
    }

    /**
     * @test
     * @dataProvider provideSqlQueryParameters
     */
    public function shouldRegisterAndExecuteFunctionWhichBypassApiFilter(array $queryParameters): void
    {
        $client = new SimpleClient(['data' => 'some data']);
        $expected = [
            'data' => 'some data',
            'query' => 'SELECT * FROM table',
        ];

        $result = $this->apiFilter
            ->registerFunction(
                'sql',
                ['query'],
                function (SimpleClient $filterable, FunctionParameter $query) {
                    return $filterable->query($query->getValue()->getValue());
                }
            )
            ->executeFunction('sql', $queryParameters, $client);

        $this->assertSame($expected, $result);
    }

    public function provideSqlQueryParameters(): array
    {
        return [
            // queryParameters
            'implicit - single value' => [['sql' => 'SELECT * FROM table']],
            'explicit - tuple' => [['(function,query)' => '(sql, "SELECT * FROM table")']],
            'implicit - single values' => [['query' => 'SELECT * FROM table']],
            'explicit - single values' => [['function' => ['sql'], 'query' => 'SELECT * FROM table']],
            'explicit - filter' => [['filter' => ['(sql, SELECT * FROM table)']]],
        ];
    }

    /**
     * @test
     */
    public function shouldNotAllowToDeclareFunctionsWithSameParameters(): void
    {
        $this->expectException(ApiFilterExceptionInterface::class);
        $this->expectExceptionMessage('There is already a function "person1" with parameter "name" registered. Parameters must be unique.');

        $this->apiFilter
            ->declareFunction('person1', ['name', 'age'])
            ->declareFunction('person2', ['name', 'ageTo', 'ageFrom']);
    }

    /**
     * @test
     */
    public function shouldNotAllowToRegisterFunctionsWithSameParameters(): void
    {
        $this->expectException(ApiFilterExceptionInterface::class);
        $this->expectExceptionMessage('There is already a function "person1" with parameter "name" registered. Parameters must be unique.');

        $this->apiFilter
            ->registerFunction(
                'person1',
                ['name', 'age'],
                $this->createDummyCallback('person1')
            )
            ->registerFunction(
                'person2',
                ['name', 'ageTo', 'ageFrom'],
                $this->createDummyCallback('person2')
            );
    }
}
