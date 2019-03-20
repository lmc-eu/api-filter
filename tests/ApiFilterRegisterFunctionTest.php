<?php declare(strict_types=1);

namespace Lmc\ApiFilter;

use Doctrine\ORM\QueryBuilder;
use Lmc\ApiFilter\Applicator\SqlApplicator;
use Lmc\ApiFilter\Constant\Priority;
use Lmc\ApiFilter\Exception\ApiFilterExceptionInterface;
use Lmc\ApiFilter\Filter\FunctionParameter;
use Lmc\ApiFilter\Fixture\SimpleClient;

class ApiFilterRegisterFunctionTest extends AbstractTestCase
{
    /** @var ApiFilter */
    private $apiFilter;
    /** @var QueryBuilder */
    private $queryBuilder;

    protected function setUp(): void
    {
        $this->queryBuilder = $this->setUpQueryBuilder();

        $this->apiFilter = new ApiFilter();
        $this->apiFilter->registerApplicator(new SqlApplicator(), Priority::HIGHEST);
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
