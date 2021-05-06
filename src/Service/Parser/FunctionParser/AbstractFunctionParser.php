<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser\FunctionParser;

use Lmc\ApiFilter\Assertion;
use Lmc\ApiFilter\Constant\Filter;
use Lmc\ApiFilter\Service\FilterFactory;
use Lmc\ApiFilter\Service\Functions;
use Lmc\ApiFilter\Service\Parser\AbstractParser;
use MF\Collection\Immutable\Tuple;
use MF\Collection\Mutable\Generic\IMap;

abstract class AbstractFunctionParser extends AbstractParser implements FunctionParserInterface
{
    private const ERROR_MULTIPLE_FUNCTION_CALL = 'It is not allowed to call one function multiple times.';
    protected const ERROR_FUNCTION_DEFINITION_BY_TUPLE_WITHOUT_TUPLE_VALUES = 'Function definition by a tuple must have a tuple value.';

    private ?array $queryParameters = null;
    /** @var IMap<string,bool>|IMap|null */
    private ?IMap $alreadyParsedFunctions;
    /** @var IMap<string,bool>|IMap|null */
    private ?IMap $alreadyParsedColumns;

    public function __construct(FilterFactory $filterFactory, protected Functions $functions)
    {
        parent::__construct($filterFactory);
    }

    public function setCommonValues(
        array $queryParameters,
        IMap $alreadyParsedFunctions,
        IMap $alreadyParsedColumns
    ): void {
        $this->queryParameters = $this->normalizeQueryParameters($queryParameters);
        $this->alreadyParsedFunctions = $alreadyParsedFunctions;
        $this->alreadyParsedColumns = $alreadyParsedColumns;
    }

    final public function supports(string $rawColumn, string|array $rawValue): bool
    {
        return $this->supportsParameters($this->assertQueryParameters(), $rawColumn, $rawValue);
    }

    abstract protected function supportsParameters(
        array $queryParameters,
        string $rawColumn,
        string|array $rawValue
    ): bool;

    private function assertQueryParameters(): array
    {
        Assertion::notNull($this->queryParameters, 'Query parameters must be set to FunctionParser.');

        return $this->queryParameters;
    }

    final public function parse(string $rawColumn, string|array $rawValue): iterable
    {
        yield from $this->parseParameters($this->assertQueryParameters(), $rawColumn, $rawValue);
    }

    abstract protected function parseParameters(
        array $queryParameters,
        string $rawColumn,
        string|array $rawValue
    ): iterable;

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

    protected function parseFunctionParameter(string $parameter, string|array $rawValue): iterable
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

    protected function validateTupleValue(string|array $rawValue, string $errorMessage): string
    {
        Assertion::isTuple($rawValue, $errorMessage);
        Assertion::false(is_array($rawValue), $errorMessage);

        return $rawValue;
    }

    protected function parseRawValueFromTuple(string $rawValue, ?int $expectedParametersCount): array
    {
        return array_map(
            fn (mixed $value) => $this->normalizeRawValue($value),
            Tuple::parse($rawValue, $expectedParametersCount)->toArray()
        );
    }

    protected function normalizeRawValue(mixed $value): string|array
    {
        return is_array($value)
            ? array_map(fn (mixed $value) => $this->normalizeRawValue($value), $value)
            : (string) $value;
    }

    protected function normalizeQueryParameters(array $queryParameters): array
    {
        return array_map(fn (mixed $value) => $this->normalizeRawValue($value), $queryParameters);
    }
}
