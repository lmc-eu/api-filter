<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser;

use Lmc\ApiFilter\Exception\InvalidArgumentException;
use Lmc\ApiFilter\Service\Functions;

/**
 * @covers \Lmc\ApiFilter\Service\Parser\FunctionParser
 * @covers \Lmc\ApiFilter\Service\Parser\FunctionParser\AbstractFunctionParser
 */
class FunctionParserTest extends AbstractParserTestCase
{
    /** @var FunctionParser */
    protected $parser;
    /** @var Functions */
    private $functions;

    protected function setUp(): void
    {
        $this->functions = new Functions();

        $this->parser = new FunctionParser($this->mockFilterFactory(), $this->functions);

        $this->functions->register('fullName', ['firstName', 'surname'], $this->createDummyCallback('fullName'));
        $this->functions->register('sql', ['query'], $this->createDummyCallback('sql'));
    }

    /**
     * @param mixed $rawValue Value from query parameters
     *
     * @test
     * @dataProvider provideParseableColumnAndValue
     */
    public function shouldSupportColumnAndValue(string $rawColumn, $rawValue): void
    {
        $this->parser->setQueryParameters([$rawColumn => $rawValue]);
        parent::shouldSupportColumnAndValue($rawColumn, $rawValue);
    }

    /**
     * @param mixed $rawValue Value from query parameters
     *
     * @test
     * @dataProvider provideNotSupportedColumnAndValue
     */
    public function shouldNotSupportColumnAndValue(string $rawColumn, $rawValue): void
    {
        $this->parser->setQueryParameters([$rawColumn => $rawValue]);
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
     * @param mixed $rawValue Value from query parameters
     *
     * @test
     * @dataProvider provideParseableColumnAndValue
     */
    public function shouldParseColumnAndValue(string $rawColumn, $rawValue, array $expected): void
    {
        $this->parser->setQueryParameters([$rawColumn => $rawValue]);
        parent::shouldParseColumnAndValue($rawColumn, $rawValue, $expected);
    }

    public function provideParseableColumnAndValue(): array
    {
        return [
            // rawColumn, rawValue, expectedFilters
            // nothing for now
        ];
    }

    /**
     * @test
     * @dataProvider provideParseableQueryParameters
     */
    public function shouldSupportQueryParameters(array $queryParameters): void
    {
        $this->parser->setQueryParameters($queryParameters);

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
        $this->parser->setQueryParameters($queryParameters);

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
        $this->parser->setQueryParameters($queryParameters);
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
            'explicit sql by values' => [
                ['function' => ['sql'], 'query' => 'SELECT * FROM table'],
                [
                    ['sql', 'function', 'callable'],
                    ['query', 'function_parameter', 'SELECT * FROM table'],
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

        $this->parser->setQueryParameters($queryParameters);

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
        $this->parser->setQueryParameters([$column => $value]);

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
        $this->parser->setQueryParameters([$column => $value]);

        $this->assertTrue($this->parser->supports($column, $value));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Explicit function definition must have a tuple value.');

        foreach ($this->parser->parse($column, $value) as $filter) {
            // just iterate through
            continue;
        }
    }
}
