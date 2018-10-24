<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Applicator;

use Lmc\ApiFilter\Filter\FilterInterface;

abstract class AbstractApplicator implements ApplicatorInterface
{
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
        $pieces = [$filter->getTitle()];

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

    protected function getColumnMultiPlaceholders(string $prefix, FilterInterface $filter): string
    {
        $values = $filter->getValue()->getValue();

        $placeholders = [];
        $i = 0;
        foreach ($values as $value) {
            $placeholders[] = $this->createColumnPlaceholder($prefix, $filter, (string) $i++);
        }

        return implode(', ', $placeholders);
    }
}
