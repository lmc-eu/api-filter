<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Entity;

use Lmc\ApiFilter\AbstractTestCase;
use Lmc\ApiFilter\Exception\InvalidArgumentException;

class ParameterDefinitionTest extends AbstractTestCase
{
    /**
     * @test
     */
    public function shouldCreateParameterWithDefaults(): void
    {
        $parameter = new ParameterDefinition('foo');

        $this->assertSame('foo', $parameter->getName());
        $this->assertSame('foo', $parameter->getColumn());
        $this->assertSame('eq', $parameter->getFilter());
        $this->assertFalse($parameter->hasDefaultValue());
        $this->assertSame('foo_function_parameter', $parameter->getTitleForDefaultValue());
    }

    /**
     * @test
     */
    public function shouldCreateParameterWithAllValues(): void
    {
        $defaultValue = new Value('boo');
        $parameter = new ParameterDefinition('foo', 'gte', 'bar', $defaultValue);

        $this->assertSame('foo', $parameter->getName());
        $this->assertSame('bar', $parameter->getColumn());
        $this->assertSame('gte', $parameter->getFilter());
        $this->assertTrue($parameter->hasDefaultValue());
        $this->assertSame($defaultValue, $parameter->getDefaultValue());
        $this->assertSame('foo_function_parameter', $parameter->getTitleForDefaultValue());
    }

    /**
     * @test
     */
    public function shouldCreateParameterByArray(): void
    {
        $parameter = ParameterDefinition::fromArray(['foo', 'gte', 'bar', 'boo']);

        $this->assertSame('foo', $parameter->getName());
        $this->assertSame('bar', $parameter->getColumn());
        $this->assertSame('gte', $parameter->getFilter());
        $this->assertTrue($parameter->hasDefaultValue());
        $this->assertSame('boo', $parameter->getDefaultValue()->getValue());
        $this->assertSame('foo_function_parameter', $parameter->getTitleForDefaultValue());
    }

    /**
     * @test
     */
    public function shouldCreateParameterByArrayWithDefaults(): void
    {
        $parameter = ParameterDefinition::fromArray(['foo']);

        $this->assertSame('foo', $parameter->getName());
        $this->assertSame('foo', $parameter->getColumn());
        $this->assertSame('eq', $parameter->getFilter());
        $this->assertFalse($parameter->hasDefaultValue());
        $this->assertSame('foo_function_parameter', $parameter->getTitleForDefaultValue());
    }

    /**
     * @test
     */
    public function shouldCreateParameterWithDefaultsAndDefaultValue(): void
    {
        $defaultValue = new Value('bar');
        $parameter = new ParameterDefinition('foo', null, null, $defaultValue);

        $this->assertSame('foo', $parameter->getName());
        $this->assertSame('foo', $parameter->getColumn());
        $this->assertSame('eq', $parameter->getFilter());
        $this->assertTrue($parameter->hasDefaultValue());
        $this->assertSame($defaultValue, $parameter->getDefaultValue());
        $this->assertSame('foo_function_parameter', $parameter->getTitleForDefaultValue());
    }

    /**
     * @test
     */
    public function shouldCreateParameterByArrayWithDefaultsAndDefaultValue(): void
    {
        $parameter = ParameterDefinition::fromArray(['foo', null, null, 'bar']);

        $this->assertSame('foo', $parameter->getName());
        $this->assertSame('foo', $parameter->getColumn());
        $this->assertSame('eq', $parameter->getFilter());
        $this->assertTrue($parameter->hasDefaultValue());
        $this->assertSame('bar', $parameter->getDefaultValue()->getValue());
        $this->assertSame('foo_function_parameter', $parameter->getTitleForDefaultValue());
    }

    /**
     * @test
     */
    public function shouldCreateParameterWithDefaultValueOnly(): void
    {
        $defaultValue = new Value('bar');
        $parameter = ParameterDefinition::equalToDefaultValue('foo', $defaultValue);

        $this->assertSame('foo', $parameter->getName());
        $this->assertSame('foo', $parameter->getColumn());
        $this->assertSame('eq', $parameter->getFilter());
        $this->assertTrue($parameter->hasDefaultValue());
        $this->assertSame($defaultValue, $parameter->getDefaultValue());
        $this->assertSame('foo_function_parameter', $parameter->getTitleForDefaultValue());
    }

    /**
     * @test
     */
    public function shouldNotGetDefaultValueIfNotSet(): void
    {
        $parameter = new ParameterDefinition('foo');

        $this->assertFalse($parameter->hasDefaultValue());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Default value is not set for "foo".');

        $parameter->getDefaultValue();
    }
}
