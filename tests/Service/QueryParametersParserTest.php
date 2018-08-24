<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service;

use Lmc\ApiFilter\AbstractTestCase;
use Lmc\ApiFilter\Entity\Value;
use Lmc\ApiFilter\Filter\FilterIn;
use Lmc\ApiFilter\Filter\FilterWithOperator;
use Lmc\ApiFilter\Filters\Filters;

class QueryParametersParserTest extends AbstractTestCase
{
    /** @var QueryParametersParser */
    private $queryParametersParser;

    protected function setUp(): void
    {
        $this->queryParametersParser = new QueryParametersParser();
    }

    /**
     * @test
     * @dataProvider provideQueryParameters
     */
    public function shouldParseQueryParameters(array $queryParameters, array $expectedFilters): void
    {
        $expectedFilters = Filters::from($expectedFilters);

        $result = $this->queryParametersParser->parse($queryParameters);

        $this->assertEquals($expectedFilters, $result);
    }

    public function provideQueryParameters(): array
    {
        return [
            // queryParameters, expectedFilters
            'empty' => [[], []],
            'simple eq' => [
                ['title' => 'foo'],
                [new FilterWithOperator('title', new Value('foo'), '=', 'eq')],
            ],
            'two cols eq' => [
                ['title' => 'foo', 'value' => 'bar'],
                [
                    new FilterWithOperator('title', new Value('foo'), '=', 'eq'),
                    new FilterWithOperator('value', new Value('bar'), '=', 'eq'),
                ],
            ],
            'eq by array' => [
                ['title' => ['eq' => 'foo']],
                [new FilterWithOperator('title', new Value('foo'), '=', 'eq')],
            ],
            'one col more filters' => [
                ['title' => ['eq' => 'foo', 'gt' => 'abc']],
                [
                    new FilterWithOperator('title', new Value('foo'), '=', 'eq'),
                    new FilterWithOperator('title', new Value('abc'), '>', 'gt'),
                ],
            ],
            'one col more filters + other col' => [
                ['title' => ['gt' => '0', 'lt' => '10'], 'value' => 'foo'],
                [
                    new FilterWithOperator('title', new Value('0'), '>', 'gt'),
                    new FilterWithOperator('title', new Value('10'), '<', 'lt'),
                    new FilterWithOperator('value', new Value('foo'), '=', 'eq'),
                ],
            ],
            'one col min max' => [
                ['title' => ['gte' => '0', 'lte' => '10']],
                [
                    new FilterWithOperator('title', new Value('0'), '>=', 'gte'),
                    new FilterWithOperator('title', new Value('10'), '<=', 'lt'),
                ],
            ],
            'in array' => [
                ['color' => ['in' => ['red', 'green', 'blue']]],
                [
                    new FilterIn('color', new Value(['red', 'green', 'blue'])),
                ],
            ],
            'eq + in array' => [
                ['allowed' => true, 'id' => ['in' => [1, 2, 3]]],
                [
                    new FilterWithOperator('allowed', new Value(true), '=', 'eq'),
                    new FilterIn('id', new Value([1, 2, 3])),
                ],
            ],
            'tuple - eq + in array' => [
                ['(zone,bucket)' => '(lmc,all)', 'id' => ['in' => [1, 2, 3]]],
                [
                    new FilterWithOperator('zone', new Value('lmc'), '=', 'eq'),
                    new FilterWithOperator('bucket', new Value('all'), '=', 'eq'),
                    new FilterIn('id', new Value([1, 2, 3])),
                ],
            ],
            'tuple - between' => [
                ['(number,alpha)' => ['gte' => '(0, a)', 'lt' => '(10, z)']],
                [
                    new FilterWithOperator('number', new Value('0'), '>=', 'gte'),
                    new FilterWithOperator('alpha', new Value('a'), '>=', 'gte'),
                    new FilterWithOperator('number', new Value('10'), '<', 'lt'),
                    new FilterWithOperator('alpha', new Value('z'), '<', 'lt'),
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provideInvalidQueryParameters
     */
    public function shouldThrowInvalidArgumentExceptionOnUnknownFilter(
        array $queryParameters,
        string $expectedMessage
    ): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->queryParametersParser->parse($queryParameters);
    }

    public function provideInvalidQueryParameters(): array
    {
        return [
            // queryParameters
            'empty filter' => [
                ['column' => ['' => 'value']],
                'Filter "" is not implemented. For column "column" with value "value".',
            ],
            'unknown filter' => [
                ['column' => ['unknown' => 'value']],
                'Filter "unknown" is not implemented. For column "column" with value "value".',
            ],
            'invalid tuple - too much values' => [
                ['(id,name)' => ['eq' => '(42,foo,bar)']],
                'Invalid tuple given - expected 2 items but parsed 3 items from "(42,foo,bar)".',
            ],
            'invalid tuple - insufficient values' => [
                ['(id, name, type)' => ['eq' => '(42, foo)']],
                'Invalid tuple given - expected 3 items but parsed 2 items from "(42, foo)".',
            ],
            'tuples in IN filter' => [
                ['(id, name)' => ['in' => ['(1,one)', '(2,two)']]],
                'Tuples are not allowed in IN filter.',
            ],
        ];
    }
}
