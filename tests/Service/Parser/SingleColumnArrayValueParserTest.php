<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser;

/**
 * @covers \Lmc\ApiFilter\Service\Parser\SingleColumnArrayValueParser
 */
class SingleColumnArrayValueParserTest extends AbstractParserTestCase
{
    protected function setUp(): void
    {
        $this->parser = new SingleColumnArrayValueParser($this->mockFilterFactory());
    }

    public function provideNotSupportedColumnAndValue(): array
    {
        return self::CASE_SCALAR_COLUMN_AND_SCALAR_VALUE
            + self::CASE_TUPLE_COLUMN_WITH_FILTER_AND_TUPLE_VALUE
            + self::CASE_TUPLE_COLUMN_AND_TUPLE_VALUE
            + self::CASE_TUPLE_COLUMN_AND_TUPLE_VALUE_IMPLICIT_FILTERS
            + self::CASE_SCALAR_COLUMN_AND_TUPLE_VALUE
            + self::CASE_TUPLE_COLUMN_AND_SCALAR_VALUE
            + self::CASE_TUPLE_COLUMN_AND_ARRAY_VALUE;
    }

    public function provideParseableColumnAndValue(): array
    {
        return self::CASE_SCALAR_COLUMN_AND_ARRAY_VALUE
            + self::CASE_SCALAR_COLUMN_AND_ARRAY_VALUES;
    }
}
