<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Filter;

use Lmc\ApiFilter\Assertion;
use Lmc\ApiFilter\Constant\Filter;
use Lmc\ApiFilter\Entity\Value;

class FilterFunction extends AbstractFilter
{
    public const TITLE = Filter::FUNCTION;

    public function __construct(string $column, Value $value, ?string $title = null)
    {
        $this->assertValueIsCallback($value);
        parent::__construct($title ?? self::TITLE, $column, $value);
    }

    private function assertValueIsCallback(Value $value): void
    {
        Assertion::isCallable($value->getValue(), 'Value for filter function must be callable. "%s" given.');
    }
}
