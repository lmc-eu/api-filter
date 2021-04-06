<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service;

use Lmc\ApiFilter\Assertion;
use Lmc\ApiFilter\Entity\Filterable;
use Lmc\ApiFilter\Entity\ParameterDefinition;
use Lmc\ApiFilter\Filter\FilterInterface;
use Lmc\ApiFilter\Filters\FiltersInterface;
use MF\Collection\Mutable\Generic\IMap;
use MF\Collection\Mutable\Generic\Map;

class Functions
{
    private const FUNCTION_NAME_TYPE = 'string';

    /** @var IMap<string, callable> */
    private IMap $functions;
    /** @var IMap<string, array> */
    private IMap $functionParameters;
    /** @var IMap<string, array<ParameterDefinition>> */
    private IMap $parameterDefinitions;
    /** @var array<string, string> parameterName => functionName */
    private array $registeredParameters;

    public function __construct()
    {
        $this->functions = new Map(self::FUNCTION_NAME_TYPE, 'callable');
        $this->functionParameters = new Map(self::FUNCTION_NAME_TYPE, 'array');
        $this->parameterDefinitions = new Map(self::FUNCTION_NAME_TYPE, 'array');
        $this->registeredParameters = [];
    }

    /**
     * @param string[] $parameters names of parameters in strings
     * @param callable $function (mixed<T> $filterable, FunctionParameter ...$parameters) -> mixed<T>
     * @param ParameterDefinition[] $parameterDefinitions
     */
    public function register(
        string $functionName,
        array $parameters,
        callable $function,
        array $parameterDefinitions = []
    ): void {
        Assertion::notEmpty($functionName, 'Function name must be defined.');
        Assertion::notEmpty($parameters, sprintf('Function "%s" must have some parameters.', $functionName));
        $this->assertUniqueParameters($functionName, $parameters);

        $this->functions[$functionName] = $function;
        $this->functionParameters[$functionName] = $parameters;
        $this->parameterDefinitions[$functionName] = $parameterDefinitions;
    }

    private function assertUniqueParameters(string $functionName, array $parameters): void
    {
        foreach ($parameters as $parameter) {
            Assertion::keyNotExists(
                $this->registeredParameters,
                $parameter,
                sprintf(
                    'There is already a function "%s" with parameter "%s" registered. Parameters must be unique.',
                    $this->registeredParameters[$parameter] ?? '-', // this is because of eager evaluation of sprintf
                    $parameter
                )
            );
            $this->registeredParameters[$parameter] = $functionName;
        }
    }

    public function getFunction(string $functionName): callable
    {
        $this->assertRegistered($functionName);

        return $this->functions[$functionName];
    }

    private function assertRegistered(string $functionName): void
    {
        Assertion::true(
            $this->isFunctionRegistered($functionName),
            sprintf('Function "%s" is not registered.', $functionName)
        );
    }

    public function isFunctionRegistered(string $functionName): bool
    {
        return $this->functions->containsKey($functionName);
    }

    public function getParametersFor(string $functionName): array
    {
        $this->assertRegistered($functionName);

        return $this->functionParameters[$functionName];
    }

    public function getFunctionNamesByParameter(string $possibleParameter): iterable
    {
        foreach ($this->functionParameters as $functionName => $parameters) {
            foreach ($parameters as $parameter) {
                if ($parameter === $possibleParameter) {
                    yield $functionName;
                    continue 2;
                }
            }
        }
    }

    public function getFunctionNamesByAllParameters(array $possibleParameters): iterable
    {
        $sortedPossibleParameters = $this->sort($possibleParameters);

        foreach ($this->functionParameters as $functionName => $parameters) {
            if ($this->sort($parameters) === $sortedPossibleParameters) {
                yield $functionName;
            }
        }
    }

    private function sort(array $array): array
    {
        sort($array);

        return $array;
    }

    /** @return ParameterDefinition[] */
    public function getParameterDefinitionsFor(string $functionName): array
    {
        $this->assertRegistered($functionName);

        return $this->parameterDefinitions[$functionName];
    }

    /** @param FiltersInterface|FilterInterface[] $filters */
    public function execute(string $functionName, FiltersInterface $filters, Filterable $filterable): Filterable
    {
        $this->assertRegistered($functionName);
        $function = $this->functions[$functionName];
        $parameters = $this->functionParameters[$functionName];

        $functionParameters = $filters->filterByColumns($parameters);
        $this->assertFiltersByParameters($parameters, $functionParameters);

        $applied = $function($filterable->getValue(), ...$functionParameters);

        return new Filterable($applied);
    }

    private function assertFiltersByParameters(array $parameters, FiltersInterface $filterByParameters): void
    {
        Assertion::same(
            count($filterByParameters),
            count($parameters),
            'There are not filters (%s) for parameters (%s).'
        );
    }
}
