<?php declare(strict_types=1);

namespace Lmc\ApiFilter;

use Lmc\ApiFilter\Exception\InvalidArgumentException;
use MF\Collection\Immutable\ITuple;
use MF\Collection\Immutable\Tuple;

/**
 * @group unit
 */
class AssertionTest extends AbstractTestCase
{
    /**
     * @test
     * @dataProvider provideValidTuple
     *
     * @param string|ITuple $tuple
     */
    public function shouldAssertTuple($tuple): void
    {
        $this->assertTrue(Assertion::isTuple($tuple));
    }

    public function provideValidTuple(): array
    {
        return [
            // tuple
            'ITuple instance' => [Tuple::of(1, 2)],
            'tuple in string' => ['(1, 2)'],
            'tuple in string without tail parentheses' => ['(1, 2'],
        ];
    }

    /**
     * @test
     * @dataProvider provideInvalidTuple
     *
     * @param mixed $tuple Invalid tuple
     */
    public function shouldThrowExceptionOnInvalidTuple($tuple, string $expectedMessage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        Assertion::isTuple($tuple);
    }

    public function provideInvalidTuple(): array
    {
        return [
            // invalidTuple
            'empty string' => ['', 'Value "" is not a Tuple.'],
            'tuple without parentheses' => ['1, 2', 'Value "1, 2" is not a Tuple.'],
            'array ' => [[1, 2], 'Value "<ARRAY>" is not a Tuple.'],
        ];
    }
}
