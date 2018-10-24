<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Entity;

use Lmc\ApiFilter\AbstractTestCase;

class FilterableTest extends AbstractTestCase
{
    /**
     * @test
     * @dataProvider provideValidFilterable
     *
     * @param mixed $validFilterable of type <T>
     */
    public function shouldCreateFilterable($validFilterable): void
    {
        $filterable = new Filterable($validFilterable);

        $this->assertSame($validFilterable, $filterable->getValue());
    }

    public function provideValidFilterable(): array
    {
        return [
            // validValue
            'null' => [null],
            'string' => ['string'],
            'int' => [42],
            'float' => [4.2],
            'bool' => [true],
            'array' => [[1, 'two', 3.3]],
            'value' => [new Value('foo')],
            'query builder' => [$this->setUpQueryBuilder('t')],
        ];
    }

    /**
     * @test
     */
    public function shouldNotCreateFilterableFromFilterable(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Filterable must not contain another Filterable. Extract a value from Filterable or use it directly.');

        new Filterable(new Filterable('nested filterable'));
    }
}
