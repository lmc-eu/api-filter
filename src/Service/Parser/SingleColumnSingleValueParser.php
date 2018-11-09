<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser;

use Lmc\ApiFilter\Constant\Filter;

class SingleColumnSingleValueParser extends AbstractParser
{
    public function supports(string $rawColumn, $rawValue): bool
    {
        return !$this->isTuple($rawColumn) && !is_array($rawValue) && !$this->isTuple($rawValue);
    }

    public function parse(string $rawColumn, $rawValue): iterable
    {
        yield $this->createFilter($rawColumn, Filter::EQUALS, $rawValue);
    }
}
