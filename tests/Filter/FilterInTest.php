<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Filter;

use Lmc\ApiFilter\AbstractTestCase;
use Lmc\ApiFilter\Entity\Value;

class FilterInTest extends AbstractTestCase
{
    /**
     * @test
     */
    public function shouldCreateFilterInWithProperDefaults(): void
    {
        $filterIn = new FilterIn('col', new Value([1, 2, 3]));

        $this->assertSame('col_in', $filterIn->getTitle());
    }

    /**
     * @test
     * @dataProvider provideValues
     *
     * @param mixed $values of type <T>
     */
    public function shouldCreateFilterIn($values, array $expected): void
    {
        $filterIn = new FilterIn('column', new Value($values));
        $filterInValues = $filterIn->getValue()->getValue();

        $this->assertSame($expected, $filterInValues);
    }

    public function provideValues(): array
    {
        return [
            // values, expected
            'empty array' => [[], []],
            'array' => [[1, 2, 3], [1, 2, 3]],
            'string' => ['foo', ['foo']],
            'int' => [42, [42]],
            'bool' => [true, [true]],
        ];
    }

    /**
     * @test
     * @dataProvider provideInvalidValues
     *
     * @param mixed $invalidValues Unsupported value type
     */
    public function shouldNotCreateFilterIn($invalidValues): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new FilterIn('column', new Value($invalidValues));
    }

    public function provideInvalidValues(): array
    {
        return [
            // invalidValues
            'iterable - iterator' => [
                new class() implements \IteratorAggregate {
                    public function getIterator(): iterable
                    {
                        return new \ArrayIterator([1, 2, 3]);
                    }
                },
            ],
        ];
    }

    /**
     * @test
     */
    public function shouldCreateFilterInAndAddValues(): void
    {
        $inFilter = new FilterIn('column', new Value(1));
        $inFilter->addValue(new Value(2));

        $valueContent = $inFilter->getValue()->getValue();

        $this->assertSame([1, 2], $valueContent);
    }

    /**
     * @test
     */
    public function shouldOverrideDefaultTitleByFullTitle(): void
    {
        $filter = new FilterIn('column', new Value('value'), 'title');
        $this->assertSame('column_title', $filter->getTitle());

        $filter->setFullTitle('full_title');
        $this->assertSame('full_title', $filter->getTitle());
    }
}
