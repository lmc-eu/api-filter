<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser;

use Lmc\ApiFilter\Constant\Priority;
use Lmc\ApiFilter\Service\FilterFactory;
use Lmc\ApiFilter\Service\Functions;
use Lmc\ApiFilter\Service\Parser\FunctionParser\ExplicitFunctionDefinitionByTupleParser;
use Lmc\ApiFilter\Service\Parser\FunctionParser\ExplicitFunctionDefinitionByValueParser;
use Lmc\ApiFilter\Service\Parser\FunctionParser\ExplicitFunctionDefinitionParser;
use Lmc\ApiFilter\Service\Parser\FunctionParser\FunctionInFilterParameterParser;
use Lmc\ApiFilter\Service\Parser\FunctionParser\FunctionParserInterface;
use Lmc\ApiFilter\Service\Parser\FunctionParser\ImplicitFunctionDefinitionByTupleParser;
use Lmc\ApiFilter\Service\Parser\FunctionParser\ImplicitFunctionDefinitionByValueParser;
use MF\Collection\Mutable\Generic\IMap;
use MF\Collection\Mutable\Generic\PrioritizedCollection;

class FunctionParser extends AbstractParser
{
    /** @var PrioritizedCollection|FunctionParserInterface[] */
    private $parsers;

    public function __construct(FilterFactory $filterFactory, Functions $functions)
    {
        parent::__construct($filterFactory);

        $this->parsers = new PrioritizedCollection(FunctionParserInterface::class);
        $this->parsers->add(new FunctionInFilterParameterParser($filterFactory, $functions), Priority::HIGHEST);
        $this->parsers->add(new ExplicitFunctionDefinitionByValueParser($filterFactory, $functions), Priority::HIGHER);
        $this->parsers->add(new ExplicitFunctionDefinitionParser($filterFactory, $functions), Priority::HIGH);
        $this->parsers->add(new ImplicitFunctionDefinitionByValueParser($filterFactory, $functions), Priority::MEDIUM);
        $this->parsers->add(new ExplicitFunctionDefinitionByTupleParser($filterFactory, $functions), Priority::LOW);
        $this->parsers->add(new ImplicitFunctionDefinitionByTupleParser($filterFactory, $functions), Priority::LOWER);
    }

    public function setQueryParameters(
        array $queryParameters,
        IMap $alreadyParsedFunctions,
        IMap $alreadyParsedColumns
    ): void {
        foreach ($this->parsers as $parser) {
            $parser->setCommonValues($queryParameters, $alreadyParsedFunctions, $alreadyParsedColumns);
        }
    }

    public function supports(string $rawColumn, $rawValue): bool
    {
        foreach ($this->parsers as $parser) {
            if ($parser->supports($rawColumn, $rawValue)) {
                return true;
            }
        }

        return false;
    }

    public function parse(string $rawColumn, $rawValue): iterable
    {
        foreach ($this->parsers as $parser) {
            if ($parser->supports($rawColumn, $rawValue)) {
                yield from $parser->parse($rawColumn, $rawValue);
            }
        }
    }
}
