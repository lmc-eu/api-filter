<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser;

use Assert\Assertion;
use Lmc\ApiFilter\Entity\Value;
use Lmc\ApiFilter\Filter\FilterInterface;
use Lmc\ApiFilter\Service\FilterFactory;

abstract class AbstractParser implements ParserInterface
{
    /** @var FilterFactory */
    private $filterFactory;

    public function __construct(FilterFactory $filterFactory)
    {
        $this->filterFactory = $filterFactory;
    }

    protected function isTuple($value): bool
    {
        return is_string($value) && mb_substr($value, 0, 1) === '(';
    }

    protected function assertColumnsAndValuesCount(int $countColumns, int $countValues): void
    {
        Assertion::same(
            $countColumns,
            $countValues,
            sprintf(
                'Number of given columns (%d) and values (%d) in tuple are not same.',
                $countColumns,
                $countValues
            )
        );
    }

    protected function isColumnWithFilter(string $column): bool
    {
        return mb_strpos($column, '[') !== false || mb_strpos($column, ']') !== false;
    }

    protected function createFilter(string $column, string $filter, $value): FilterInterface
    {
        return $this->filterFactory->createFilter($column, $filter, new Value($value));
    }
}
