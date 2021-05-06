<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser;

use Lmc\ApiFilter\Exception\InvalidArgumentException;

/**
 * @covers \Lmc\ApiFilter\Service\Parser\TupleColumnArrayValueParser
 */
class TupleColumnArrayValueParserTest extends AbstractParserTestCase
{
    protected function setUp(): void
    {
        $this->parser = new TupleColumnArrayValueParser($this->mockFilterFactory());
    }

    public function provideNotSupportedColumnAndValue(): array
    {
        return self::CASE_TUPLE_COLUMN_AND_TUPLE_VALUE
            + self::CASE_TUPLE_COLUMN_WITH_FILTER_AND_TUPLE_VALUE
            + self::CASE_SCALAR_COLUMN_AND_ARRAY_VALUE
            + self::CASE_TUPLE_COLUMN_AND_TUPLE_VALUE_IMPLICIT_FILTERS
            + self::CASE_SCALAR_COLUMN_AND_TUPLE_VALUE
            + self::CASE_TUPLE_COLUMN_AND_SCALAR_VALUE
            + self::CASE_SCALAR_COLUMN_AND_SCALAR_VALUE
            + self::CASE_SCALAR_COLUMN_AND_ARRAY_VALUES;
    }

    public function provideParseableColumnAndValue(): array
    {
        return self::CASE_TUPLE_COLUMN_AND_ARRAY_VALUE;
    }

    /**
     * @test
     * @dataProvider provideInvalidQueryParameters
     */
    public function shouldNotCreateFilterForInvalidQueryParameters(
        string $rawColumn,
        string|array $rawValue,
        string $expectedMessage
    ): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        foreach ($this->parser->parse($rawColumn, $rawValue) as $item) {
            $this->fail('This should not get here.');
        }
    }

    public function provideInvalidQueryParameters(): array
    {
        return [
            // rawColumn, rawValue, expectedMessage
            'in filter in tuple' => [
                '(col1,col2)',
                ['in' => '([1;2],[3;4])'],
                'Tuples are not allowed in IN filter.',
            ],
            'filter in both column an value' => [
                '(col1[gt],col2[lt])',
                ['gte' => '(1,3)'],
                'Filters can be specified either in columns or in values - not in both',
            ],
        ];
    }
}
