<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser\FunctionParser;

use Lmc\ApiFilter\Exception\InvalidArgumentException;

/**
 * @group unit
 * @covers \Lmc\ApiFilter\Service\Parser\FunctionParser\ExplicitFunctionDefinitionParser
 */
class ExplicitFunctionDefinitionParserTest extends AbstractFunctionParserTestCase
{
    protected function setUp(): void
    {
        $functions = $this->initFunctions();
        $functions->register('sql', ['query'], $this->createDummyCallback('sql'));

        $this->parser = new ExplicitFunctionDefinitionParser($this->mockFilterFactory(), $functions);
    }

    public function provideNotQueryParameters(): array
    {
        return self::CASE_EXPLICIT_FUNCTION_DEFINITION_BY_VALUES
            + self::CASE_IMPLICIT_FUNCTION_DEFINITION_BY_VALUES
            + self::CASE_EXPLICIT_FUNCTION_DEFINITION_BY_TUPLE
            + self::CASE_IMPLICIT_FUNCTION_DEFINITION_BY_TUPLE
            + self::CASE_FUNCTION_IN_FILTER_PARAMETER;
    }

    public function provideParseableQueryParameters(): array
    {
        return self::CASE_EXPLICIT_FUNCTION_DEFINITION
            + [
                'single value function - sql' => [
                    ['sql' => 'SELECT * FROM table'],
                    [
                        ['sql', 'function', 'callable'],
                        ['query', 'function_parameter', 'SELECT * FROM table'],
                    ],
                ],
            ];
    }

    /**
     * @test
     * @dataProvider provideInvalidSingleValue
     *
     * @param string|array $value
     */
    public function shouldParseSingleParameterFunctionWithSingleValueOnly($value): void
    {
        $column = 'sql';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A single parameter function definition must have a single value.');

        $this->parseQueryParameters([$column => $value]);
    }

    public function provideInvalidSingleValue(): array
    {
        return [
            // value
            'array' => [['query1', 'query2']],
            'tuple' => ['(query1, query2)'],
        ];
    }

    /** @test */
    public function shouldParseFunctionWithMoreParametersWithTupleValuesOnly(): void
    {
        $this->expectExceptionMessage(InvalidArgumentException::class);
        $this->expectExceptionMessage('Explicit function definition must have a tuple value.');

        $this->parseQueryParameters(['fullName' => 'Jon']);
    }
}
