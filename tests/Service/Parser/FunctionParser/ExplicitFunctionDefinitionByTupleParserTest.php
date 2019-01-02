<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser\FunctionParser;

use Lmc\ApiFilter\Exception\InvalidArgumentException;
use MF\Collection\Exception\TupleParseException;

/**
 * @group unit
 * @covers \Lmc\ApiFilter\Service\Parser\FunctionParser\ExplicitFunctionDefinitionByTupleParser
 */
class ExplicitFunctionDefinitionByTupleParserTest extends AbstractFunctionParserTestCase
{
    protected function setUp(): void
    {
        $this->parser = new ExplicitFunctionDefinitionByTupleParser($this->mockFilterFactory(), $this->initFunctions());
    }

    public function provideNotQueryParameters(): array
    {
        return self::CASE_EXPLICIT_FUNCTION_DEFINITION
            + self::CASE_IMPLICIT_FUNCTION_DEFINITION_BY_VALUES
            + self::CASE_EXPLICIT_FUNCTION_DEFINITION_BY_VALUES
            + self::CASE_IMPLICIT_FUNCTION_DEFINITION_BY_TUPLE
            + self::CASE_FUNCTION_IN_FILTER_PARAMETER;
    }

    public function provideParseableQueryParameters(): array
    {
        return self::CASE_EXPLICIT_FUNCTION_DEFINITION_BY_TUPLE;
    }

    /**
     * @test
     * @dataProvider provideFullNameQueryParametersInDifferentOrder
     */
    public function shouldParseExplicitTupleDefinitionInAnyOrderOfParameters(array $queryParameters): void
    {
        $result = $this->parseQueryParameters($queryParameters);

        $this->assertSame(
            [
                ['fullName', 'function', 'callable'],
                ['firstName', 'function_parameter', 'Jon'],
                ['surname', 'function_parameter', 'Snow'],
            ],
            $result
        );
    }

    public function provideFullNameQueryParametersInDifferentOrder(): array
    {
        return [
            // queryParameters
            'firstName, surname' => [['(function,firstName,surname)' => '(fullName, Jon, Snow)']],
            'surname, firstName' => [['(function,surname,firstName)' => '(fullName, Snow, Jon)']],
        ];
    }

    /** @test */
    public function shouldNotParseFunctionWhenValueIsNotTuple(): void
    {
        $queryParameters = ['(function,firstName,surname)' => 'not-a-tuple'];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Function definition by a tuple must have a tuple value.');

        $this->parseQueryParameters($queryParameters);
    }

    /** @test */
    public function shouldNotParseFunctionWhenValueHasLessValuesThanColumn(): void
    {
        $queryParameters = ['(function,firstName,surname)' => '(fullName,Jon)'];

        $this->expectException(TupleParseException::class);
        $this->expectExceptionMessage('Invalid tuple given - expected 3 items but parsed 2 items from "(fullName,Jon)".');

        $this->parseQueryParameters($queryParameters);
    }

    /** @test */
    public function shouldNotParseFunctionWhenValueHasMoreValuesThanColumn(): void
    {
        $queryParameters = ['(function,firstName,surname)' => '(fullName,Jon,Snow,Knows-nothing)'];

        $this->expectException(TupleParseException::class);
        $this->expectExceptionMessage('Invalid tuple given - expected 3 items but parsed 4 items from "(fullName,Jon,Snow,Knows-nothing)".');

        $this->parseQueryParameters($queryParameters);
    }
}
