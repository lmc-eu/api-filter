<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser\FunctionParser;

use Lmc\ApiFilter\Exception\InvalidArgumentException;

/**
 * @group unit
 * @covers \Lmc\ApiFilter\Service\Parser\FunctionParser\ExplicitFunctionDefinitionByValueParser
 */
class ExplicitFunctionDefinitionByValueParserTest extends AbstractFunctionParserTestCase
{
    protected function setUp(): void
    {
        $this->parser = new ExplicitFunctionDefinitionByValueParser($this->mockFilterFactory(), $this->initFunctions());
    }

    public function provideNotQueryParameters(): array
    {
        return self::CASE_EXPLICIT_FUNCTION_DEFINITION
            + self::CASE_IMPLICIT_FUNCTION_DEFINITION_BY_VALUES
            + self::CASE_EXPLICIT_FUNCTION_DEFINITION_BY_TUPLE
            + self::CASE_IMPLICIT_FUNCTION_DEFINITION_BY_TUPLE
            + self::CASE_FUNCTION_IN_FILTER_PARAMETER;
    }

    public function provideParseableQueryParameters(): array
    {
        return self::CASE_EXPLICIT_FUNCTION_DEFINITION_BY_VALUES;
    }

    /** @test */
    public function shouldNotParseFunctionWhenDefinitionIsNotArray(): void
    {
        $queryParameters = ['function' => 'not-an-array'];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Explicit function definition by values must be an array of functions. not-an-array given.');

        $this->parseQueryParameters($queryParameters);
    }

    /** @test */
    public function shouldNotParseFunctionWithoutAllParameters(): void
    {
        $queryParameters = [
            'function' => ['fullName'],
            'firstName' => 'Jon',
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('There is a missing parameter surname for a function fullName.');

        $this->parseQueryParameters($queryParameters);
    }

    /** @test */
    public function shouldNotParseNotRegisteredFunction(): void
    {
        $queryParameters = ['function' => ['not-registered-function']];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Function "not-registered-function" is not registered.');

        $this->parseQueryParameters($queryParameters);
    }
}
