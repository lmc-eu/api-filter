<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service;

use Lmc\ApiFilter\Assertion;
use Lmc\ApiFilter\Entity\ParameterDefinition;
use MF\Collection\Mutable\Generic\IMap;
use MF\Collection\Mutable\Generic\Map;

class Functions
{
    private const FUNCTION_NAME_TYPE = 'string';

    /** @var IMap<string, callable> */
    private $functions;
    /** @var IMap<string, array> */
    private $functionParameters;
    /** @var IMap<string, array<ParameterDefinition>> */
    private $parameterDefinitions;
    /** @var array<string, string> parameterName => functionName */
    private $registeredParameters;

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
}
