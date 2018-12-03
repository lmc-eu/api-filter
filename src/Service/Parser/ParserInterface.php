<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser;

interface ParserInterface
{
    /**
     * @param string|array $rawValue Raw value from query parameters
     */
    public function supports(string $rawColumn, $rawValue): bool;

    /**
     * @param string|array $rawValue Raw value from query parameters
     */
    public function parse(string $rawColumn, $rawValue): iterable;
}
