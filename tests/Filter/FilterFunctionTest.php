<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Filter;

use Lmc\ApiFilter\AbstractTestCase;
use Lmc\ApiFilter\Entity\Value;

class FilterFunctionTest extends AbstractTestCase
{
    /**
     * @test
     */
    public function shouldCreateFilterFunctionWithProperDefaults(): void
    {
        $filterFunction = new FilterFunction('fullName', new Value($this->createDummyCallback('fullName')));

        $this->assertSame('fullName_function', $filterFunction->getTitle());
    }

    /**
     * @test
     */
    public function shouldCreateFilterFunction(): void
    {
        $filterFunction = new FilterFunction(
            'fooFunction',
            new Value(function () {
                return 'fooBar';
            }),
            'foo'
        );

        $function = $filterFunction->getValue()->getValue();

        $this->assertSame('fooFunction', $filterFunction->getColumn());
        $this->assertSame('fooFunction_foo', $filterFunction->getTitle());
        $this->assertInternalType('callable', $function, 'Function in filter is not callable');
        $this->assertSame('fooBar', $function());
    }

    /**
     * @test
     */
    public function shouldNotCreateFilterFunction(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Value for filter function must be callable. "not-callable" given.');

        new FilterFunction('column', new Value('not-callable'));
    }
}
