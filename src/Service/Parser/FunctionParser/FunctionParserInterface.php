<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser\FunctionParser;

use Lmc\ApiFilter\Service\Parser\ParserInterface;
use MF\Collection\Mutable\Generic\IMap;

interface FunctionParserInterface extends ParserInterface
{
    public function setCommonValues(
        array $queryParameters,
        IMap $alreadyParsedFunctions,
        IMap $alreadyParsedColumns
    ): void;
}
