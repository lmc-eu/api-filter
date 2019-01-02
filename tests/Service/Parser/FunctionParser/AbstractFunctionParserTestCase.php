<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser\FunctionParser;

use Lmc\ApiFilter\AbstractTestCase;
use Lmc\ApiFilter\Service\Functions;
use Lmc\ApiFilter\Service\Parser\Fixtures\SimpleFilter;
use MF\Collection\Mutable\Generic\Map;

abstract class AbstractFunctionParserTestCase extends AbstractTestCase
{
    private const CASES = [
        self::CASE_EXPLICIT_FUNCTION_DEFINITION,
        self::CASE_EXPLICIT_FUNCTION_DEFINITION_BY_VALUES,
        self::CASE_IMPLICIT_FUNCTION_DEFINITION_BY_VALUES,
        self::CASE_EXPLICIT_FUNCTION_DEFINITION_BY_TUPLE,
        self::CASE_IMPLICIT_FUNCTION_DEFINITION_BY_TUPLE,
        self::CASE_FUNCTION_IN_FILTER_PARAMETER,
    ];

    protected const CASE_EXPLICIT_FUNCTION_DEFINITION = [
        'explicit function definition - fullName' => [
            ['fullName' => '(Jon,Snow)'],
            [
                ['fullName', 'function', 'callable'],
                ['firstName', 'function_parameter', 'Jon'],
                ['surname', 'function_parameter', 'Snow'],
            ],
        ],
    ];
    protected const CASE_EXPLICIT_FUNCTION_DEFINITION_BY_VALUES = [
        'explicit function definition by values - fullName' => [
            ['function' => ['fullName'], 'firstName' => 'Jon', 'surname' => 'Snow'],
            [
                ['fullName', 'function', 'callable'],
                ['firstName', 'function_parameter', 'Jon'],
                ['surname', 'function_parameter', 'Snow'],
            ],
        ],
    ];
    protected const CASE_IMPLICIT_FUNCTION_DEFINITION_BY_VALUES = [
        'implicit function definition by values - fullName' => [
            ['firstName' => 'Jon', 'surname' => 'Snow'],
            [
                ['fullName', 'function', 'callable'],
                ['firstName', 'function_parameter', 'Jon'],
                ['surname', 'function_parameter', 'Snow'],
            ],
        ],
    ];
    protected const CASE_EXPLICIT_FUNCTION_DEFINITION_BY_TUPLE = [
        'explicit function definition by tuple - fullName' => [
            ['(function,firstName,surname)' => '(fullName, Jon, Snow)'],
            [
                ['fullName', 'function', 'callable'],
                ['firstName', 'function_parameter', 'Jon'],
                ['surname', 'function_parameter', 'Snow'],
            ],
        ],
    ];
    protected const CASE_IMPLICIT_FUNCTION_DEFINITION_BY_TUPLE = [
        'implicit function definition by tuple - fullName' => [
            ['(firstName,surname)' => '(Jon, Snow)'],
            [
                ['fullName', 'function', 'callable'],
                ['firstName', 'function_parameter', 'Jon'],
                ['surname', 'function_parameter', 'Snow'],
            ],
        ],
    ];
    protected const CASE_FUNCTION_IN_FILTER_PARAMETER = [
        'function in filter parameter - fullName' => [
            ['filter' => ['(fullName, Jon, Snow)']],
            [
                ['fullName', 'function', 'callable'],
                ['firstName', 'function_parameter', 'Jon'],
                ['surname', 'function_parameter', 'Snow'],
            ],
        ],
    ];

    /** @var FunctionParserInterface */
    protected $parser;

    protected function initFunctions(): Functions
    {
        $functions = new Functions();
        $functions->register('fullName', ['firstName', 'surname'], $this->createDummyCallback('fullName'));

        return $functions;
    }

    /**
     * @test
     * @dataProvider provideParseableQueryParameters
     */
    public function shouldSupportQueryParameters(array $queryParameters): void
    {
        $this->setCommonValuesToParser($queryParameters);

        $result = false;
        foreach ($queryParameters as $rawColumn => $rawValue) {
            $result = $result || $this->parser->supports($rawColumn, $rawValue);
        }

        $this->assertTrue($result);
    }

    private function setCommonValuesToParser(array $queryParameters): void
    {
        $this->parser->setCommonValues($queryParameters, new Map('string', 'bool'), new Map('string', 'bool'));
    }

    /**
     * @test
     * @dataProvider provideNotQueryParameters
     */
    public function shouldNotSupportColumnAndValue(array $queryParameters): void
    {
        $this->setCommonValuesToParser($queryParameters);

        $result = true;
        foreach ($queryParameters as $rawColumn => $rawValue) {
            $result = $result && $this->parser->supports($rawColumn, $rawValue);
        }

        $this->assertFalse($result);
    }

    abstract public function provideNotQueryParameters(): array;

    /**
     * @test
     * @dataProvider provideParseableQueryParameters
     */
    public function shouldParseColumnAndValue(array $queryParameters, array $expected): void
    {
        $result = $this->parseQueryParameters($queryParameters);

        $this->assertSame($expected, $result);
    }

    protected function parseQueryParameters(array $queryParameters): array
    {
        $this->setCommonValuesToParser($queryParameters);
        $result = [];

        foreach ($queryParameters as $rawColumn => $rawValue) {
            /** @var SimpleFilter $filter */
            foreach ($this->parser->parse($rawColumn, $rawValue) as $filter) {
                $this->assertInstanceOf(SimpleFilter::class, $filter);
                $result[] = $filter->toArray();
            }
        }

        return $result;
    }

    abstract public function provideParseableQueryParameters(): array;

    /**
     * @test
     */
    final public function shouldCoverAllCases(): void
    {
        $cases = $this->provideParseableQueryParameters() + $this->provideNotQueryParameters();

        foreach (self::CASES as $case) {
            [$caseKey] = array_keys($case);
            $this->assertArrayHasKey(
                $caseKey,
                $cases,
                sprintf(
                    'Function parser test must cover all cases. You are missing a case "%s".',
                    $caseKey
                )
            );
        }
    }
}
