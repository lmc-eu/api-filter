<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service;

use Lmc\ApiFilter\Constant\Priority;
use Lmc\ApiFilter\Exception\TupleException;
use Lmc\ApiFilter\Filter\FilterInterface;
use Lmc\ApiFilter\Filters\Filters;
use Lmc\ApiFilter\Filters\FiltersInterface;
use Lmc\ApiFilter\Service\Parser\FunctionParser;
use Lmc\ApiFilter\Service\Parser\ParserInterface;
use Lmc\ApiFilter\Service\Parser\SingleColumnArrayValueParser;
use Lmc\ApiFilter\Service\Parser\SingleColumnSingleValueParser;
use Lmc\ApiFilter\Service\Parser\TupleColumnArrayValueParser;
use Lmc\ApiFilter\Service\Parser\TupleColumnTupleValueParser;
use Lmc\ApiFilter\Service\Parser\UnsupportedTupleCombinationParser;
use MF\Collection\Exception\TupleExceptionInterface;
use MF\Collection\Mutable\Generic\IMap;
use MF\Collection\Mutable\Generic\Map;
use MF\Collection\Mutable\Generic\PrioritizedCollection;

class QueryParametersParser
{
    /** @var PrioritizedCollection|ParserInterface[] */
    private $parsers;
    /** @var FunctionParser */
    private $functionParser;
    /** @var IMap<string,bool>|IMap */
    private $alreadyParsedFunctions;
    /** @var IMap<string,bool>|IMap */
    private $alreadyParsedColumns;

    public function __construct(FilterFactory $filterFactory, Functions $functions)
    {
        $this->functionParser = new FunctionParser($filterFactory, $functions);
        $this->alreadyParsedFunctions = new Map('string', 'bool');
        $this->alreadyParsedColumns = new Map('string', 'bool');

        $this->parsers = new PrioritizedCollection(ParserInterface::class);
        $this->parsers->add($this->functionParser, Priority::HIGHER);
        $this->parsers->add(new TupleColumnTupleValueParser($filterFactory), Priority::HIGH);
        $this->parsers->add(new TupleColumnArrayValueParser($filterFactory), Priority::MEDIUM);
        $this->parsers->add(new UnsupportedTupleCombinationParser($filterFactory), Priority::LOW);
        $this->parsers->add(new SingleColumnArrayValueParser($filterFactory), Priority::LOWER);
        $this->parsers->add(new SingleColumnSingleValueParser($filterFactory), Priority::LOWEST);
    }

    public function parse(array $queryParameters): FiltersInterface
    {
        try {
            $this->functionParser->setQueryParameters(
                $queryParameters,
                $this->alreadyParsedFunctions,
                $this->alreadyParsedColumns
            );

            $filters = new Filters();
            foreach ($this->parseFilters($queryParameters) as $filter) {
                $filters->addFilter($filter);
            }

            return $filters;
        } catch (TupleExceptionInterface $e) {
            throw TupleException::forBaseTupleException($e);
        }
    }

    /** @return FilterInterface[] */
    private function parseFilters(array $queryParameters): iterable
    {
        foreach ($queryParameters as $rawColumn => $rawValue) {
            if ($this->alreadyParsedFunctions->containsKey($rawColumn) || $this->alreadyParsedColumns->containsKey($rawColumn)) {
                continue;
            }

            foreach ($this->parsers as $parser) {
                if ($parser->supports($rawColumn, $rawValue)) {
                    yield from $parser->parse($rawColumn, $rawValue);

                    // continue to next query parameter
                    continue 2;
                }
            }
        }
    }
}
