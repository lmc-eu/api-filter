<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser;

use Lmc\ApiFilter\Exception\InvalidArgumentException;
use Lmc\ApiFilter\Service\FilterFactory;

/**
 * @covers \Lmc\ApiFilter\Service\Parser\UnsupportedTupleCombinationParser
 */
class UnsupportedTupleCombinationParserTest extends AbstractParserTestCase
{
    protected function setUp(): void
    {
        $this->parser = new UnsupportedTupleCombinationParser(new FilterFactory());
    }

    public function provideNotSupportedColumnAndValue(): array
    {
        return self::CASE_SCALAR_COLUMN_AND_SCALAR_VALUE
            + self::CASE_SCALAR_COLUMN_AND_ARRAY_VALUE
            + self::CASE_SCALAR_COLUMN_AND_ARRAY_VALUES;
    }

    /**
     * @test
     * @dataProvider provideParseableColumnAndValue
     */
    public function shouldParseColumnAndValue(string $rawColumn, mixed $rawValue, array $expected): void
    {
        $this->expectException(InvalidArgumentException::class);

        parent::shouldParseColumnAndValue($rawColumn, $rawValue, $expected);
    }

    public function provideParseableColumnAndValue(): array
    {
        return self::CASE_TUPLE_COLUMN_AND_TUPLE_VALUE
            + self::CASE_SCALAR_COLUMN_AND_TUPLE_VALUE
            + self::CASE_TUPLE_COLUMN_AND_SCALAR_VALUE
            + self::CASE_TUPLE_COLUMN_AND_ARRAY_VALUE
            + self::CASE_TUPLE_COLUMN_WITH_FILTER_AND_TUPLE_VALUE
            + self::CASE_TUPLE_COLUMN_AND_TUPLE_VALUE_IMPLICIT_FILTERS;
    }

    /**
     * @test
     * @dataProvider provideUnsupportedColumnAndValue
     */
    public function shouldNotSupportParsing(mixed $rawColumn, mixed $rawValue, string $expectedMessage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        foreach ($this->parser->parse($rawColumn, $rawValue) as $item) {
            $this->fail('This should not get here.');
        }
    }

    public function provideUnsupportedColumnAndValue(): array
    {
        return [
            // column, value, expectedMessage
            'tuple column + tuple value' => [
                '(col1,col2)',
                '(val1,val2)',
                'Invalid combination of a tuple and a scalar. Column (col1,col2) and value (val1,val2).',
            ],
            'scalar column + tuple value' => [
                'column',
                '(val1,val2)',
                'Invalid combination of a tuple and a scalar. Column column and value (val1,val2).',
            ],
            'tuple column + scalar value' => [
                '(col1,col2)',
                'value',
                'Invalid combination of a tuple and a scalar. Column (col1,col2) and value value.',
            ],
            'tuple column + array value' => [
                '(col1,col2)',
                ['filter' => 'value'],
                'Invalid combination of a tuple and a scalar. Column (col1,col2) and value [filter => value].',
            ],
            'tuple column + nested array value' => [
                '(col1,col2)',
                ['filter' => ['foo' => ['bar', 'baz']]],
                'Invalid combination of a tuple and a scalar. Column (col1,col2) and value [filter => [foo => [bar, baz]]].',
            ],
        ];
    }
}
