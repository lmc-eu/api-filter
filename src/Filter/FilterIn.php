<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Filter;

use Lmc\ApiFilter\Assertion;
use Lmc\ApiFilter\Constant\Filter;
use Lmc\ApiFilter\Entity\Value;

class FilterIn extends AbstractFilter
{
    public const TITLE = Filter::IN;

    public function __construct(string $column, Value $value, ?string $title = null)
    {
        parent::__construct($title ?? self::TITLE, $column, $this->sanitizeValue($value));
    }

    private function sanitizeValue(Value $value): Value
    {
        $valueContent = $value->getValue();
        if (is_scalar($valueContent)) {
            $value = new Value([$valueContent]);
        }

        Assertion::isArray($value->getValue(), 'Value for IN filter must be array or scalar. "%s" given.');

        return $value;
    }

    public function addValue(Value $value): void
    {
        $currentValues = $this->getValue()->getValue();
        $valuesToAdd = $this->sanitizeValue($value)->getValue();

        $this->setValue(new Value(array_merge($currentValues, $valuesToAdd)));
    }
}
