<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Entity;

use Lmc\ApiFilter\AbstractTestCase;

class ValueTest extends AbstractTestCase
{
    /**
     * @test
     * @dataProvider provideValidValue
     *
     * @param mixed $validValue of type <T>
     */
    public function shouldCreateValue($validValue): void
    {
        $value = new Value($validValue);

        $this->assertSame($validValue, $value->getValue());
    }

    public function provideValidValue(): array
    {
        return [
            // validValue
            'null' => [null],
            'string' => ['string'],
            'int' => [42],
            'float' => [4.2],
            'bool' => [true],
            'array' => [[1, 'two', 3.3]],
            'object' => [new Filterable('foo')],
        ];
    }

    /**
     * @test
     */
    public function shouldNotCreateValueFromValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Value must not contain another Value. Extract a value from Value or use it directly.');

        new Value(new Value('nested value'));
    }
}
