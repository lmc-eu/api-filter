<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Applicator;

use Lmc\ApiFilter\Entity\Value;
use Lmc\ApiFilter\Escape\EscapeInterface;
use Lmc\ApiFilter\Filter\FilterInterface;

abstract class AbstractApplicator implements ApplicatorInterface
{
    /** @var EscapeInterface|null */
    private $escape;

    public function setEscape(EscapeInterface $escape): void
    {
        $this->escape = $escape;
    }

    protected function escape(string $column, Value $value): Value
    {
        return $this->escape && $this->escape->supports($column, $value)
            ? $this->escape->escape($column, $value)
            : $value;
    }

    public function getPreparedValue(FilterInterface $filter): array
    {
        $values = $filter->getValue()->getValue();

        return is_iterable($values)
            ? $this->getPreparedMultiValues($filter)
            : $this->getPreparedSingleValue($filter);
    }

    protected function getPreparedMultiValues(FilterInterface $filter): array
    {
        $preparedValues = [];
        $i = 0;
        foreach ($filter->getValue()->getValue() as $value) {
            $preparedValues[$this->createColumnPlaceholder('', $filter, (string) $i++)] = $value;
        }

        return $preparedValues;
    }

    private function createColumnPlaceholder(string $prefix, FilterInterface $filter, string $additional = null): string
    {
        $pieces = [$filter->getColumn(), $filter->getTitle()];

        if ($additional !== null) {
            $pieces[] = $additional;
        }

        return $prefix . implode('_', $pieces);
    }

    protected function getPreparedSingleValue(FilterInterface $filter): array
    {
        return [$this->createColumnPlaceholder('', $filter) => $filter->getValue()->getValue()];
    }

    protected function getColumnSinglePlaceholder(string $prefix, FilterInterface $filter): string
    {
        return $this->createColumnPlaceholder($prefix, $filter);
    }
}
