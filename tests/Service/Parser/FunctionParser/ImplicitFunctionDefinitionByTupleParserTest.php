<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser\FunctionParser;

use Lmc\ApiFilter\Exception\InvalidArgumentException;

/**
 * @group unit
 * @covers \Lmc\ApiFilter\Service\Parser\FunctionParser\ImplicitFunctionDefinitionByTupleParser
 */
class ImplicitFunctionDefinitionByTupleParserTest extends AbstractFunctionParserTestCase
{
    protected function setUp(): void
    {
        $this->parser = new ImplicitFunctionDefinitionByTupleParser($this->mockFilterFactory(), $this->initFunctions());
    }

    public function provideNotQueryParameters(): array
    {
        return self::CASE_EXPLICIT_FUNCTION_DEFINITION
            + self::CASE_IMPLICIT_FUNCTION_DEFINITION_BY_VALUES
            + self::CASE_EXPLICIT_FUNCTION_DEFINITION_BY_VALUES
            + self::CASE_EXPLICIT_FUNCTION_DEFINITION_BY_TUPLE
            + self::CASE_FUNCTION_IN_FILTER_PARAMETER;
    }

    public function provideParseableQueryParameters(): array
    {
        return self::CASE_IMPLICIT_FUNCTION_DEFINITION_BY_TUPLE;
    }

    /** @test */
    public function shouldNotParseFunctionWhenValueIsNotTuple(): void
    {
        $queryParameters = ['(firstName,surname)' => 'not-a-tuple'];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Function definition by a tuple must have a tuple value.');

        $this->parseQueryParameters($queryParameters);
    }
}
