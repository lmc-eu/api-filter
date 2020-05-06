<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser;

use Lmc\ApiFilter\Assertion;

class SingleColumnArrayValueParser extends AbstractParser
{
    public function supports(string $rawColumn, $rawValue): bool
    {
        return !$this->isTuple($rawColumn) && is_array($rawValue);
    }

    public function parse(string $rawColumn, $rawValue): iterable
    {
        Assertion::isArray($rawValue);
        foreach ($rawValue as $filter => $value) {
            yield $this->createFilter($rawColumn, $filter, $value);
        }
    }
}
