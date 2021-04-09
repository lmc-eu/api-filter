<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser;

use Lmc\ApiFilter\Exception\InvalidArgumentException;
use Lmc\ApiFilter\Service\Functions;
use MF\Collection\Mutable\Generic\IMap;
use MF\Collection\Mutable\Generic\Map;

/**
 * @covers \Lmc\ApiFilter\Service\Parser\FunctionParser
 * @covers \Lmc\ApiFilter\Service\Parser\FunctionParser\AbstractFunctionParser
 */
class FunctionParserTest extends AbstractParserTestCase
{
    /** @var FunctionParser */
    protected ParserInterface $parser;
    private Functions $functions;
    /** @var IMap<string,bool>|IMap */
    private IMap $alreadyParsedFunctions;
    /** @var IMap<string,bool>|IMap */
    private IMap $alreadyParsedColumns;

    protected function setUp(): void
    {
        $this->functions = new Functions();

        $this->parser = new FunctionParser($this->mockFilterFactory(), $this->functions);

        $this->functions->register('fullName', ['firstName', 'surname'], $this->createDummyCallback('fullName'));
        $this->functions->register('sql', ['query'], $this->createDummyCallback('sql'));
        $this->functions->register(
            'perfectBook',
            ['ageFrom', 'ageTo', 'size'],
            $this->createDummyCallback('perfectBook')
        );

        $this->alreadyParsedFunctions = new Map('string', 'bool');
        $this->alreadyParsedColumns = new Map('string', 'bool');
    }

    /**
     * @test
     * @dataProvider provideParseableColumnAndValue
     */
    public function shouldSupportColumnAndValue(string $rawColumn, mixed $rawValue): void
    {
        $this->parser->setQueryParameters(
            [$rawColumn => $rawValue],
            $this->alreadyParsedFunctions,
            $this->alreadyParsedColumns
        );
        parent::shouldSupportColumnAndValue($rawColumn, $rawValue);
    }

    /**
     * @test
     * @dataProvider provideNotSupportedColumnAndValue
     */
    public function shouldNotSupportColumnAndValue(string $rawColumn, mixed $rawValue): void
    {
        $this->parser->setQueryParameters(
            [$rawColumn => $rawValue],
            $this->alreadyParsedFunctions,
            $this->alreadyParsedColumns
        );
        parent::shouldNotSupportColumnAndValue($rawColumn, $rawValue);
    }

    public function provideNotSupportedColumnAndValue(): array
    {
        return self::CASE_SCALAR_COLUMN_AND_TUPLE_VALUE
            + self::CASE_TUPLE_COLUMN_AND_TUPLE_VALUE
            + self::CASE_TUPLE_COLUMN_AND_TUPLE_VALUE_IMPLICIT_FILTERS
            + self::CASE_SCALAR_COLUMN_AND_SCALAR_VALUE
            + self::CASE_SCALAR_COLUMN_AND_ARRAY_VALUE
            + self::CASE_SCALAR_COLUMN_AND_ARRAY_VALUES
            + self::CASE_TUPLE_COLUMN_WITH_FILTER_AND_TUPLE_VALUE
            + self::CASE_TUPLE_COLUMN_AND_SCALAR_VALUE
            + self::CASE_TUPLE_COLUMN_AND_ARRAY_VALUE;
    }

    /**
     * @test
     * @dataProvider provideParseableColumnAndValue
     */
    public function shouldParseColumnAndValue(string $rawColumn, mixed $rawValue, array $expected): void
    {
        $this->parser->setQueryParameters(
            [$rawColumn => $rawValue],
            $this->alreadyParsedFunctions,
            $this->alreadyParsedColumns
        );
        parent::shouldParseColumnAndValue($rawColumn, $rawValue, $expected);
    }

    public function provideParseableColumnAndValue(): array
    {
        return [
            // rawColumn, rawValue, expectedFilters
            'two functions in filter parameter' => [
                'filter',
                ['(fullName, Jon, Snow)', '(sql, "SELECT * FROM table")'],
                [
                    ['fullName', 'function', 'callable'],
                    ['firstName', 'function_parameter', 'Jon'],
                    ['surname', 'function_parameter', 'Snow'],
                    ['sql', 'function', 'callable'],
                    ['query', 'function_parameter', 'SELECT * FROM table'],
                ],
            ],
            'scalar column + tuple value - fullName' => [
                'fullName',
                '(Jon,Snow)',
                [
                    ['fullName', 'function', 'callable'],
                    ['firstName', 'function_parameter', 'Jon'],
                    ['surname', 'function_parameter', 'Snow'],
                ],
            ],
            'tuple column + tuple value - implicit fullName by tuple' => [
                '(firstName,surname)',
                '(Jon,Snow)',
                [
                    ['fullName', 'function', 'callable'],
                    ['firstName', 'function_parameter', 'Jon'],
                    ['surname', 'function_parameter', 'Snow'],
                ],
            ],
            'tuple column + tuple value - implicit perfectBook by tuple' => [
                '(ageFrom, ageTo, size)',
                '(18, 30, [A4; A5])',
                [
                    ['perfectBook', 'function', 'callable'],
                    ['ageFrom', 'function_parameter', '18'],
                    ['ageTo', 'function_parameter', '30'],
                    ['size', 'function_parameter', ['A4', 'A5']],
                ],
            ],
            'tuple column + tuple value - explicit perfectBook by tuple' => [
                '(function, ageFrom, ageTo, size)',
                '(perfectBook, 18, 30, [A4; A5])',
                [
                    ['perfectBook', 'function', 'callable'],
                    ['ageFrom', 'function_parameter', '18'],
                    ['ageTo', 'function_parameter', '30'],
                    ['size', 'function_parameter', ['A4', 'A5']],
                ],
            ],
            'scalar column + scalar value - sql' => [
                'sql',
                'SELECT * FROM table',
                [
                    ['sql', 'function', 'callable'],
                    ['query', 'function_parameter', 'SELECT * FROM table'],
                ],
            ],
            'scalar column + scalar value - implicit sql' => [
                'query',
                'SELECT * FROM table',
                [
                    ['sql', 'function', 'callable'],
                    ['query', 'function_parameter', 'SELECT * FROM table'],
                ],
            ],
            'tuple column + tuple value - explicit sql by tuple' => [
                '(function,query)',
                '(sql, "SELECT * FROM table")',
                [
                    ['sql', 'function', 'callable'],
                    ['query', 'function_parameter', 'SELECT * FROM table'],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provideParseableQueryParameters
     */
    public function shouldSupportQueryParameters(array $queryParameters): void
    {
        $this->parser->setQueryParameters($queryParameters, $this->alreadyParsedFunctions, $this->alreadyParsedColumns);

        foreach ($queryParameters as $column => $value) {
            $this->assertTrue($this->parser->supports($column, $value));
        }
    }

    /**
     * @test
     * @dataProvider provideInsufficientParametersForFunction
     */
    public function shouldNotSupportInsufficientFunctionParameters(array $queryParameters): void
    {
        $this->parser->setQueryParameters($queryParameters, $this->alreadyParsedFunctions, $this->alreadyParsedColumns);

        foreach ($queryParameters as $column => $value) {
            $this->assertFalse($this->parser->supports($column, $value));
        }
    }

    public function provideInsufficientParametersForFunction(): array
    {
        return [
            // queryParameters
            'missing surname' => [
                ['firstName' => 'Jon'],
            ],
            'mixed between two tuples' => [
                ['(foo,surname)' => '(foo,Snow)', '(firstName,bar)' => '(Jon,bar)'],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provideParseableQueryParameters
     */
    public function shouldParseQueryParameters(array $queryParameters, array $expected): void
    {
        $this->parser->setQueryParameters($queryParameters, $this->alreadyParsedFunctions, $this->alreadyParsedColumns);
        $result = [];

        foreach ($queryParameters as $column => $value) {
            foreach ($this->parseColumnAndValue($column, $value) as $item) {
                $result[] = $item;
            }
        }

        $this->assertSame($expected, $result);
    }

    public function provideParseableQueryParameters(): array
    {
        return [
            // queryParameters, expected
            'function' => [
                ['fullName' => '(Jon, Snow)'],
                [
                    ['fullName', 'function', 'callable'],
                    ['firstName', 'function_parameter', 'Jon'],
                    ['surname', 'function_parameter', 'Snow'],
                ],
            ],
            'implicit - tuple' => [
                ['(firstName, surname)' => '(Jon, Snow)'],
                [
                    ['fullName', 'function', 'callable'],
                    ['firstName', 'function_parameter', 'Jon'],
                    ['surname', 'function_parameter', 'Snow'],
                ],
            ],
            'implicit - tuple - reversed' => [
                ['(surname, firstName)' => '(Snow, Jon)'],
                [
                    ['fullName', 'function', 'callable'],
                    ['surname', 'function_parameter', 'Snow'],
                    ['firstName', 'function_parameter', 'Jon'],
                ],
            ],
            'implicit - values' => [
                ['firstName' => 'Jon', 'surname' => 'Snow'],
                [
                    ['fullName', 'function', 'callable'],
                    ['firstName', 'function_parameter', 'Jon'],
                    ['surname', 'function_parameter', 'Snow'],
                ],
            ],
            'explicit - tuple' => [
                ['(function,firstName, surname)' => '(fullName,Jon, Snow)'],
                [
                    ['fullName', 'function', 'callable'],
                    ['firstName', 'function_parameter', 'Jon'],
                    ['surname', 'function_parameter', 'Snow'],
                ],
            ],
            'explicit - values' => [
                ['function' => ['fullName'], 'firstName' => 'Jon', 'surname' => 'Snow'],
                [
                    ['fullName', 'function', 'callable'],
                    ['firstName', 'function_parameter', 'Jon'],
                    ['surname', 'function_parameter', 'Snow'],
                ],
            ],
            'sql by single value' => [
                ['sql' => 'SELECT * FROM table'],
                [
                    ['sql', 'function', 'callable'],
                    ['query', 'function_parameter', 'SELECT * FROM table'],
                ],
            ],
            'explicit sql by tuple' => [
                ['(function,query)' => '(sql, "SELECT * FROM table")'],
                [
                    ['sql', 'function', 'callable'],
                    ['query', 'function_parameter', 'SELECT * FROM table'],
                ],
            ],
            'explicit sql by values' => [
                ['function' => ['sql'], 'query' => 'SELECT * FROM table'],
                [
                    ['sql', 'function', 'callable'],
                    ['query', 'function_parameter', 'SELECT * FROM table'],
                ],
            ],
            'implicit sql by value' => [
                ['query' => 'SELECT * FROM table'],
                [
                    ['sql', 'function', 'callable'],
                    ['query', 'function_parameter', 'SELECT * FROM table'],
                ],
            ],
            // multiple functions
            'multiple functions' => [
                ['fullName' => '(Jon, Snow)', 'perfectBook' => '(18, 30, [A4; A5])'],
                [
                    ['fullName', 'function', 'callable'],
                    ['firstName', 'function_parameter', 'Jon'],
                    ['surname', 'function_parameter', 'Snow'],
                    ['perfectBook', 'function', 'callable'],
                    ['ageFrom', 'function_parameter', '18'],
                    ['ageTo', 'function_parameter', '30'],
                    ['size', 'function_parameter', ['A4', 'A5']],
                ],
            ],
            'multiple functions - in filter and explicit' => [
                ['perfectBook' => '(18, 30, [A4; A5])', 'filter' => '(fullName, Jon, Snow)'],
                [
                    ['fullName', 'function', 'callable'],
                    ['firstName', 'function_parameter', 'Jon'],
                    ['surname', 'function_parameter', 'Snow'],
                    ['perfectBook', 'function', 'callable'],
                    ['ageFrom', 'function_parameter', '18'],
                    ['ageTo', 'function_parameter', '30'],
                    ['size', 'function_parameter', ['A4', 'A5']],
                ],
            ],
            'multiple - implicit - tuple' => [
                ['(firstName, surname)' => '(Jon, Snow)', '(ageFrom,ageTo,size)' => '(18, 30, [A4; A5])'],
                [
                    ['fullName', 'function', 'callable'],
                    ['firstName', 'function_parameter', 'Jon'],
                    ['surname', 'function_parameter', 'Snow'],
                    ['perfectBook', 'function', 'callable'],
                    ['ageFrom', 'function_parameter', '18'],
                    ['ageTo', 'function_parameter', '30'],
                    ['size', 'function_parameter', ['A4', 'A5']],
                ],
            ],
            'multiple - implicit - values' => [
                ['firstName' => 'Jon', 'surname' => 'Snow', 'ageFrom' => '18', 'ageTo' => '30', 'size' => ['A4', 'A5']],
                [
                    ['fullName', 'function', 'callable'],
                    ['firstName', 'function_parameter', 'Jon'],
                    ['surname', 'function_parameter', 'Snow'],
                    ['perfectBook', 'function', 'callable'],
                    ['ageFrom', 'function_parameter', '18'],
                    ['ageTo', 'function_parameter', '30'],
                    ['size', 'function_parameter', ['A4', 'A5']],
                ],
            ],
            'multiple - explicit - tuple' => [
                [
                    '(function,firstName, surname)' => '(fullName,Jon, Snow)',
                    '(function,ageFrom,ageTo,size)' => '(perfectBook,18,30,[A4;A5])',
                ],
                [
                    ['fullName', 'function', 'callable'],
                    ['firstName', 'function_parameter', 'Jon'],
                    ['surname', 'function_parameter', 'Snow'],
                    ['perfectBook', 'function', 'callable'],
                    ['ageFrom', 'function_parameter', '18'],
                    ['ageTo', 'function_parameter', '30'],
                    ['size', 'function_parameter', ['A4', 'A5']],
                ],
            ],
            'multiple - explicit - values' => [
                [
                    'function' => ['fullName', 'perfectBook'],
                    'size' => ['A4', 'A5'],
                    'firstName' => 'Jon',
                    'surname' => 'Snow',
                    'ageFrom' => '18',
                    'ageTo' => '30',
                ],
                [
                    ['fullName', 'function', 'callable'],
                    ['firstName', 'function_parameter', 'Jon'],
                    ['surname', 'function_parameter', 'Snow'],
                    ['perfectBook', 'function', 'callable'],
                    ['ageFrom', 'function_parameter', '18'],
                    ['ageTo', 'function_parameter', '30'],
                    ['size', 'function_parameter', ['A4', 'A5']],
                ],
            ],
        ];
    }

    /**
     * @test
     */
    public function shouldNotSupportWithoutQueryParameters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Query parameters must be set to FunctionParser.');

        $this->parser->supports('foo', 'bar');
    }

    /**
     * @test
     */
    public function shouldNotParseWithoutQueryParameters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Query parameters must be set to FunctionParser.');

        foreach ($this->parser->parse('foo', 'bar') as $filter) {
            $this->fail('This should not be reached');
        }
    }

    /**
     * @test
     */
    public function shouldNotParseFunctionByExplicitValueDefinition(): void
    {
        // ?fun=fullName&firstName=Jon&surname=Snow
        $queryParameters = ['function' => 'fullName', 'firstName' => 'Jon', 'surname' => 'Snow'];

        $this->parser->setQueryParameters($queryParameters, $this->alreadyParsedFunctions, $this->alreadyParsedColumns);

        foreach ($queryParameters as $column => $value) {
            $this->assertTrue($this->parser->supports($column, $value));
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Explicit function definition by values must be an array of functions. fullName given.');

        foreach ($queryParameters as $column => $value) {
            foreach ($this->parser->parse($column, $value) as $filter) {
                $this->fail('This should not be reached.');
            }
        }
    }

    /**
     * @test
     */
    public function shouldNotParseFunctionDefinedBadly(): void
    {
        // ?fullName=Jon,Snow
        $column = 'fullName';
        $value = 'Jon,Snow';
        $this->parser->setQueryParameters(
            [$column => $value],
            $this->alreadyParsedFunctions,
            $this->alreadyParsedColumns
        );

        $this->assertTrue($this->parser->supports($column, $value));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Explicit function definition must have a tuple value.');

        foreach ($this->parser->parse($column, $value) as $filter) {
            // just iterate through
            continue;
        }
    }

    /**
     * @test
     */
    public function shouldNotCallOneFunctionTwice(): void
    {
        // ?fullName[]=(Jon,Snow)&fullName[]=(Peter,Parker)
        $column = 'fullName';
        $value = ['(Jon,Snow)', '(Peter,Parker)'];
        $this->parser->setQueryParameters(
            [$column => $value],
            $this->alreadyParsedFunctions,
            $this->alreadyParsedColumns
        );

        $this->assertTrue($this->parser->supports($column, $value));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Explicit function definition must have a tuple value.');

        foreach ($this->parser->parse($column, $value) as $filter) {
            // just iterate through
            continue;
        }
    }

    /** @test */
    public function shouldNotCallOneFunctionTwiceByDifferentDefinitions(): void
    {
        // ?fun[]=fullName&firstName=Jon&surname=Snow&fullName=(Peter,Parker)
        $queryParameters = [
            'function' => ['fullName'],
            'firstName' => 'Jon',
            'surname' => 'Snow',
            'fullName' => '(Peter,Parker)',
        ];

        $this->parser->setQueryParameters($queryParameters, $this->alreadyParsedFunctions, $this->alreadyParsedColumns);

        foreach ($queryParameters as $column => $value) {
            $this->assertTrue($this->parser->supports($column, $value));
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('It is not allowed to call one function multiple times.');

        foreach ($queryParameters as $column => $value) {
            foreach ($this->parser->parse($column, $value) as $filter) {
                // just iterate through
                continue;
            }
        }
    }
}
