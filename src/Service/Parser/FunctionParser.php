<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser;

use Lmc\ApiFilter\Constant\Priority;
use Lmc\ApiFilter\Service\FilterFactory;
use Lmc\ApiFilter\Service\Functions;
use Lmc\ApiFilter\Service\Parser\FunctionParser\ExplicitFunctionDefinitionInValueParser;
use Lmc\ApiFilter\Service\Parser\FunctionParser\ExplicitFunctionDefinitionParser;
use Lmc\ApiFilter\Service\Parser\FunctionParser\FunctionParserInterface;
use MF\Collection\Mutable\Generic\Map;
use MF\Collection\Mutable\Generic\PrioritizedCollection;

class FunctionParser extends AbstractParser
{
    /** @var PrioritizedCollection|FunctionParserInterface[] */
    private $parsers;

    public function __construct(FilterFactory $filterFactory, Functions $functions)
    {
        parent::__construct($filterFactory);

        $this->parsers = new PrioritizedCollection(FunctionParserInterface::class);
        $this->parsers->add(new ExplicitFunctionDefinitionInValueParser($filterFactory, $functions), Priority::HIGHER);
        $this->parsers->add(new ExplicitFunctionDefinitionParser($filterFactory, $functions), Priority::HIGH);
    }

    public function setQueryParameters(array $queryParameters): void
    {
        $alreadyParsedFunctions = new Map('string', 'bool');
        $alreadyParsedQueryParameters = new Map('string', 'bool');

        foreach ($this->parsers as $parser) {
            $parser->setCommonValues($queryParameters, $alreadyParsedFunctions, $alreadyParsedQueryParameters);
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
