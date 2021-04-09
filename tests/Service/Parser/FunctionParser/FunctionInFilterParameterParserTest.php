<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser\FunctionParser;

use Lmc\ApiFilter\Exception\InvalidArgumentException;
use Lmc\ApiFilter\Service\Functions;
use Lmc\ApiFilter\Service\Parser\Fixtures\SimpleFilter;
use MF\Collection\Mutable\Generic\Map;

/**
 * @group unit
 * @covers \Lmc\ApiFilter\Service\Parser\FunctionParser\FunctionInFilterParameterParser
 */
class FunctionInFilterParameterParserTest extends AbstractFunctionParserTestCase
{
    private Functions $functions;

    protected function setUp(): void
    {
        $this->functions = $this->initFunctions();

        $this->parser = new FunctionInFilterParameterParser($this->mockFilterFactory(), $this->functions);
    }

    public function provideNotQueryParameters(): array
    {
        return self::CASE_EXPLICIT_FUNCTION_DEFINITION
            + self::CASE_EXPLICIT_FUNCTION_DEFINITION_BY_VALUES
            + self::CASE_EXPLICIT_FUNCTION_DEFINITION_BY_TUPLE
            + self::CASE_IMPLICIT_FUNCTION_DEFINITION_BY_TUPLE
            + self::CASE_IMPLICIT_FUNCTION_DEFINITION_BY_VALUES;
    }

    public function provideParseableQueryParameters(): array
    {
        return self::CASE_FUNCTION_IN_FILTER_PARAMETER;
    }

    /**
     * @test
     * @dataProvider provideFunctionsInFilterParameter
     */
    public function shouldParseMoreFunctionsFromFilterParameter(array $queryParameters, array $expectedFilters): void
    {
        $this->functions->register('adult', ['ageFrom'], $this->createDummyCallback('adult'));

        $result = $this->parseQueryParameters($queryParameters);

        $this->assertSame($expectedFilters, $result);
    }

    public function provideFunctionsInFilterParameter(): array
    {
        return [
            // query parameters, expected
            'direct fullName' => [
                ['filter' => '(fullName, Jon, Snow)'],
                [
                    ['fullName', 'function', 'callable'],
                    ['firstName', 'function_parameter', 'Jon'],
                    ['surname', 'function_parameter', 'Snow'],
                ],
            ],
            'fullName + adult' => [
                ['filter' => ['(fullName, Jon, Snow)', '(adult, 18)']],
                [
                    ['fullName', 'function', 'callable'],
                    ['firstName', 'function_parameter', 'Jon'],
                    ['surname', 'function_parameter', 'Snow'],
                    ['adult', 'function', 'callable'],
                    ['ageFrom', 'function_parameter', 18],
                ],
            ],
        ];
    }

    /** @test */
    public function shouldNotParseSameFunctionMoreTimes(): void
    {
        $queryParameters = [
            'filter' => [
                '(fullName, Jon, Snow)',
                '(fullName, Peter, Parker)',
            ],
        ];
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('It is not allowed to call one function multiple times.');

        $this->parseQueryParameters($queryParameters);
    }

    /**
     * @test
     * @dataProvider provideInvalidValue
     *
     * @param mixed $value
     */
    public function shouldNotParseFunctionByInvalidValue($value, string $expectedMessage): void
    {
        $queryParameters = ['filter' => $value];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->parseQueryParameters($queryParameters);
    }

    public function provideInvalidValue(): array
    {
        return [
            // value, expectedMessage
            // - invalid type
            'null' => [null, 'Filter parameter must have functions in array of in string with Tuple.'],
            'int' => [42, 'Filter parameter must have functions in array of in string with Tuple.'],
            'string' => ['just string', 'Filter parameter must have functions in array of in string with Tuple.'],
            'null in array' => [[null], 'All values in filter column must be Tuples.'],
            'int in array' => [[42], 'All values in filter column must be Tuples.'],
            'string in array' => [['just string'], 'All values in filter column must be Tuples.'],
            // - different number of parameters
            'less parameters for fullName' => [
                '(fullName, Jon)',
                'Given filter must have 2 parameters, but 1 was given.',
            ],
            'too much parameters for fullName' => [
                '(fullName, Jon, Snow, "Knows Nothing")',
                'Given filter must have 2 parameters, but 3 was given.',
            ],
        ];
    }

    /** @test */
    public function shouldNotParseFilterColumnMoreThanOnceWithoutSettingQueryParameters(): void
    {
        $queryParameters = ['filter' => '(fullName, Jon, Snow)'];
        $expected = [
            ['fullName', 'function', 'callable'],
            ['firstName', 'function_parameter', 'Jon'],
            ['surname', 'function_parameter', 'Snow'],
        ];

        $parseQueryParameters = function (array $queryParameters) {
            $result = [];

            foreach ($queryParameters as $rawColumn => $rawValue) {
                /** @var SimpleFilter $filter */
                foreach ($this->parser->parse($rawColumn, $rawValue) as $filter) {
                    $this->assertInstanceOf(SimpleFilter::class, $filter);
                    $result[] = $filter->toArray();
                }
            }

            return $result;
        };

        // set query parameters and parse query parameters for the first time
        $this->parser->setCommonValues($queryParameters, new Map('string', 'bool'), new Map('string', 'bool'));
        $result = $parseQueryParameters($queryParameters);
        $this->assertSame($expected, $result);

        // parse query parameters again and have empty result
        $result = $parseQueryParameters($queryParameters);
        $this->assertSame([], $result);

        // set query parameters (as new ones) and parse query parameters (as for the first time)
        $this->parser->setCommonValues($queryParameters, new Map('string', 'bool'), new Map('string', 'bool'));
        $result = $parseQueryParameters($queryParameters);
        $this->assertSame($expected, $result);
    }
}
