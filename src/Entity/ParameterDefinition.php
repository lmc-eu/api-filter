<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Entity;

use Lmc\ApiFilter\Assertion;
use Lmc\ApiFilter\Constant\Filter;
use Lmc\ApiFilter\Filter\FunctionParameter;

class ParameterDefinition
{
    private string $name;
    private string $filter;
    private string $column;
    private ?Value $defaultValue;

    /**
     * Shortcut for creating parameter with just a name and a default value
     * Otherwise you would need to pass null as filter and column, or create default values for yourself
     *
     * @example
     * equalToDefaultValue('name', new Value(10))   // Parameter { name: "name", filter: "eq", column: "name",  defaultValue: Value(10) }
     *
     * ☝️is same as 👇
     * fromArray(['name', null, null, 10])          // Parameter { name: "name", filter: "eq", column: "name",  defaultValue: Value(10) }
     */
    public static function equalToDefaultValue(string $name, Value $defaultValue): self
    {
        return new self($name, null, null, $defaultValue);
    }

    /**
     * This method is basically a syntax sugar around the constructor method, but it wraps a default value into Value object
     *
     * @example
     * fromArray(['name'])                    // Parameter { name: "name", filter: "eq", column: "name",  defaultValue: null }
     * fromArray(['name', 'gt'])              // Parameter { name: "name", filter: "gt", column: "name",  defaultValue: null }
     * fromArray(['name', 'gt', 'field'])     // Parameter { name: "name", filter: "gt", column: "field", defaultValue: null }
     * fromArray(['name', null, 'field'])     // Parameter { name: "name", filter: "eq", column: "field", defaultValue: null }
     * fromArray(['name', 'gt', 'field', 10]) // Parameter { name: "name", filter: "gt", column: "field", defaultValue: Value(10) }
     * fromArray(['name', null, null, 10])    // Parameter { name: "name", filter: "eq", column: "name",  defaultValue: Value(10) }
     */
    public static function fromArray(array $parameters): self
    {
        if (count($parameters) === 4) {
            $defaultValue = array_pop($parameters);
            $parameters[] = new Value($defaultValue);
        }

        return new self(...$parameters);
    }

    public function __construct(
        string $name,
        ?string $filter = Filter::EQUALS,
        ?string $column = null,
        ?Value $defaultValue = null
    ) {
        $this->name = $name;
        $this->filter = $filter ?? Filter::EQUALS;
        $this->column = $column ?? $name;
        $this->defaultValue = $defaultValue;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFilter(): string
    {
        return $this->filter;
    }

    public function getColumn(): string
    {
        return $this->column;
    }

    public function getDefaultValue(): Value
    {
        Assertion::notNull($this->defaultValue, sprintf('Default value is not set for "%s".', $this->name));

        return $this->defaultValue;
    }

    public function hasDefaultValue(): bool
    {
        return $this->defaultValue !== null;
    }

    public function getTitleForDefaultValue(): string
    {
        return sprintf('%s_%s', $this->getName(), FunctionParameter::TITLE);
    }
}
