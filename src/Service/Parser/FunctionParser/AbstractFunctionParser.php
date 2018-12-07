<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser\FunctionParser;

use Lmc\ApiFilter\Assertion;
use Lmc\ApiFilter\Constant\Filter;
use Lmc\ApiFilter\Service\FilterFactory;
use Lmc\ApiFilter\Service\Functions;
use Lmc\ApiFilter\Service\Parser\AbstractParser;
use MF\Collection\Mutable\Generic\IMap;
use MF\Collection\Mutable\Generic\Map;

abstract class AbstractFunctionParser extends AbstractParser implements FunctionParserInterface
{
    private const ERROR_MULTIPLE_FUNCTION_CALL = 'It is not allowed to call one function multiple times.';

    /** @var Functions */
    protected $functions;
    /** @var ?array */
    private $queryParameters;
    /** @var Map<string,bool>|IMap|null */
    private $alreadyParsedFunctions;
    /** @var Map<string,bool>|IMap|null */
    private $alreadyParsedColumns;

    public function __construct(FilterFactory $filterFactory, Functions $functions)
    {
        parent::__construct($filterFactory);
        $this->functions = $functions;
    }

    public function setCommonValues(
        array $queryParameters,
        IMap $alreadyParsedFunctions,
        IMap $alreadyParsedColumns
    ): void {
        $this->queryParameters = $queryParameters;
        $this->alreadyParsedFunctions = $alreadyParsedFunctions;
        $this->alreadyParsedColumns = $alreadyParsedColumns;
    }

    /**
     * @param string|array $rawValue Raw value from query parameters
     */
    final public function supports(string $rawColumn, $rawValue): bool
    {
        return $this->supportsParameters($this->assertQueryParameters(), $rawColumn, $rawValue);
    }

    abstract protected function supportsParameters(array $queryParameters, string $rawColumn, $rawValue): bool;

    private function assertQueryParameters(): array
    {
        Assertion::notNull($this->queryParameters, 'Query parameters must be set to FunctionParser.');

        return $this->queryParameters;
    }

    /**
     * @param string|array $rawValue Raw value from query parameters
     */
    final public function parse(string $rawColumn, $rawValue): iterable
    {
        yield from $this->parseParameters($this->assertQueryParameters(), $rawColumn, $rawValue);
    }

    /**
     * @param string|array $rawValue Raw value from query parameters
     */
    abstract protected function parseParameters(array $queryParameters, string $rawColumn, $rawValue): iterable;

    protected function parseFunction(string $functionName): iterable
    {
        Assertion::notNull($this->alreadyParsedFunctions, 'Already parsed functions must be set before parsing.');
        Assertion::false($this->alreadyParsedFunctions->containsKey($functionName), self::ERROR_MULTIPLE_FUNCTION_CALL);

        $this->alreadyParsedFunctions[$functionName] = true;

        yield $this->createFilter(
            $functionName,
            Filter::FUNCTION,
            $this->functions->getFunction($functionName)
        );
    }

    /**
     * @param string|array $rawValue Raw value from query parameters
     */
    protected function parseFunctionParameter(string $parameter, $rawValue): iterable
    {
        if (!$this->isColumnParsed($parameter)) {
            $this->alreadyParsedColumns[$parameter] = true;

            yield $this->createFilter($parameter, Filter::FUNCTION_PARAMETER, $rawValue);
        }
    }

    protected function isColumnParsed(string $column): bool
    {
        return $this->alreadyParsedColumns !== null
            && $this->alreadyParsedColumns->containsKey($column);
    }

    protected function markColumnAsParsed(string $column): void
    {
        Assertion::notNull($this->alreadyParsedColumns, 'Already parsed query parameters must be set before parsing.');
        $this->alreadyParsedColumns[$column] = true;
    }

    /**
     * @param string|array $rawValue Raw value from query parameters
     */
    protected function validateTupleValue($rawValue, string $errorMessage): string
    {
        Assertion::isTuple($rawValue, $errorMessage);
        Assertion::false(is_array($rawValue), $errorMessage);

        return $rawValue;
    }
}
