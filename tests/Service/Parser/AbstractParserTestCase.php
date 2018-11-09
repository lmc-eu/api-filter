<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser;

use Lmc\ApiFilter\AbstractTestCase;
use Lmc\ApiFilter\Entity\Value;
use Lmc\ApiFilter\Service\FilterFactory;
use Lmc\ApiFilter\Service\Parser\Fixtures\SimpleFilter;
use Mockery as m;

abstract class AbstractParserTestCase extends AbstractTestCase
{
    private const CASES = [
        self::CASE_SCALAR_COLUMN_AND_SCALAR_VALUE,
        self::CASE_SCALAR_COLUMN_AND_ARRAY_VALUE,
        self::CASE_SCALAR_COLUMN_AND_ARRAY_VALUES,
        self::CASE_SCALAR_COLUMN_AND_TUPLE_VALUE,
        self::CASE_TUPLE_COLUMN_AND_TUPLE_VALUE,
        self::CASE_TUPLE_COLUMN_AND_TUPLE_VALUE_IMPLICIT_FILTERS,
        self::CASE_TUPLE_COLUMN_AND_ARRAY_VALUE,
        self::CASE_TUPLE_COLUMN_WITH_FILTER_AND_TUPLE_VALUE,
        self::CASE_TUPLE_COLUMN_AND_SCALAR_VALUE,
    ];

    protected const CASE_SCALAR_COLUMN_AND_SCALAR_VALUE = [
        'scalar column + scalar value' => [
            'column',
            'value',
            [
                ['column', 'eq', 'value'],
            ],
        ],
    ];
    protected const CASE_SCALAR_COLUMN_AND_ARRAY_VALUE = [
        'scalar column + array value' => [
            'column',
            ['gt' => 'value'],
            [
                ['column', 'gt', 'value'],
            ],
        ],
    ];
    protected const CASE_SCALAR_COLUMN_AND_ARRAY_VALUES = [
        'scalar column + array values' => [
            'column',
            ['gte' => 'value', 'lte' => 'value2'],
            [
                ['column', 'gte', 'value'],
                ['column', 'lte', 'value2'],
            ],
        ],
    ];
    protected const CASE_SCALAR_COLUMN_AND_TUPLE_VALUE = [
        'scalar column + tuple value' => [
            'column',
            '(val1,val2)',
            [], // not supported atm
        ],
    ];
    protected const CASE_TUPLE_COLUMN_AND_TUPLE_VALUE = [
        'tuple column + tuple value' => [
            '(col1,col2)',
            '(val1,val2)',
            [
                ['col1', 'eq', 'val1'],
                ['col2', 'eq', 'val2'],
            ],
        ],
    ];
    protected const CASE_TUPLE_COLUMN_AND_TUPLE_VALUE_IMPLICIT_FILTERS = [
        'implicit filters in tuple column + value' => [
            '(col1,col2)',
            '(value,[min;max])',
            [
                ['col1', 'eq', 'value'],
                ['col2', 'in', ['min', 'max']],
            ],
        ],
    ];
    protected const CASE_TUPLE_COLUMN_AND_ARRAY_VALUE = [
        'tuple column + array value' => [
            '(col1,col2)',
            ['lte' => '(val1,val2)'],
            [
                ['col1', 'lte', 'val1'],
                ['col2', 'lte', 'val2'],
            ],
        ],
    ];
    protected const CASE_TUPLE_COLUMN_WITH_FILTER_AND_TUPLE_VALUE = [
        'tuple column with filter + tuple value' => [
            '(col1[gt],col2[lt])',
            '(1,10)',
            [
                ['col1', 'gt', 1],
                ['col2', 'lt', 10],
            ],
        ],
    ];
    protected const CASE_TUPLE_COLUMN_AND_SCALAR_VALUE = [
        'tuple column + scalar value' => [
            '(col1,col2)',
            'value',
            [], // not supported atm
        ],
    ];

    /** @var ParserInterface */
    protected $parser;

    protected function mockFilterFactory(): FilterFactory
    {
        $filterFactory = m::mock(FilterFactory::class);
        $filterFactory->shouldReceive('createFilter')
            ->andReturnUsing(function (string $column, string $filter, Value $value) {
                return new SimpleFilter($column, $filter, $value->getValue());
            });

        return $filterFactory;
    }

    /**
     * @param mixed $rawValue Value from query parameters
     *
     * @test
     * @dataProvider provideParseableColumnAndValue
     */
    public function shouldSupportColumnAndValue(string $rawColumn, $rawValue): void
    {
        $result = $this->parser->supports($rawColumn, $rawValue);

        $this->assertTrue($result);
    }

    /**
     * @param mixed $rawValue Value from query parameters
     *
     * @test
     * @dataProvider provideNotSupportedColumnAndValue
     */
    public function shouldNotSupportColumnAndValue(string $rawColumn, $rawValue): void
    {
        $result = $this->parser->supports($rawColumn, $rawValue);

        $this->assertFalse($result);
    }

    abstract public function provideNotSupportedColumnAndValue(): array;

    /**
     * @param mixed $rawValue Value from query parameters
     *
     * @test
     * @dataProvider provideParseableColumnAndValue
     */
    public function shouldParseColumnAndValue(string $rawColumn, $rawValue, array $expected): void
    {
        $result = $this->parseColumnAndValue($rawColumn, $rawValue);

        $this->assertSame($expected, $result);
    }

    protected function parseColumnAndValue(string $rawColumn, $rawValue): array
    {
        $result = [];

        /** @var SimpleFilter $filter */
        foreach ($this->parser->parse($rawColumn, $rawValue) as $filter) {
            $this->assertInstanceOf(SimpleFilter::class, $filter);
            $result[] = $filter->toArray();
        }

        return $result;
    }

    abstract public function provideParseableColumnAndValue(): array;

    /**
     * @test
     */
    final public function shouldCoverAllCases(): void
    {
        $cases = $this->provideParseableColumnAndValue() + $this->provideNotSupportedColumnAndValue();

        foreach (self::CASES as $case) {
            [$caseKey] = array_keys($case);
            $this->assertArrayHasKey(
                $caseKey,
                $cases,
                sprintf(
                    'Parser test must cover all cases. You are missing a case "%s".',
                    $caseKey
                )
            );
        }
    }
}
