<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser;

interface ParserInterface
{
    public function supports(string $rawColumn, string|array $rawValue): bool;

    public function parse(string $rawColumn, string|array $rawValue): iterable;
}
