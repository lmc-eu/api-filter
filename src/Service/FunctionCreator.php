<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service;

use Lmc\ApiFilter\Assertion;
use Lmc\ApiFilter\Entity\Filterable;
use Lmc\ApiFilter\Entity\ParameterDefinition;
use Lmc\ApiFilter\Exception\InvalidArgumentException;
use Lmc\ApiFilter\Filter\FunctionParameter;
use Lmc\ApiFilter\Filters\Filters;
use Lmc\ApiFilter\Filters\FiltersInterface;
use MF\Collection\Immutable\Generic\IMap;
use MF\Collection\Immutable\Generic\Map;

class FunctionCreator
{
    public function __construct(private FilterFactory $filterFactory)
    {
    }

    /** @return ParameterDefinition[]|IMap IMap<string, Parameter> */
    public function normalizeParameters(array $parameters): IMap
    {
        $normalizeParameters = new Map('string', ParameterDefinition::class);

        foreach ($parameters as $parameter) {
            $this->assertParameter($parameter);
            if (is_string($parameter)) {
                $parameter = new ParameterDefinition($parameter);
            } else {
                $parameter = $parameter instanceof ParameterDefinition
                    ? $parameter
                    : ParameterDefinition::fromArray($parameter);
            }

            $normalizeParameters = $normalizeParameters->set($parameter->getName(), $parameter);
        }

        return $normalizeParameters;
    }

    /**
     * @param ParameterDefinition[]|IMap $normalizedParameters IMap<string, Parameter>
     * @see FunctionCreator::normalizeParameters()
     */
    public function getParameterNames(IMap $normalizedParameters): array
    {
        return $normalizedParameters
            ->filter(function ($_, ParameterDefinition $parameter) {
                return !$parameter->hasDefaultValue();
            })
            ->keys()
            ->toArray();
    }

    /**
     * @param ParameterDefinition[]|IMap $normalizedParameters IMap<string, Parameter>
     * @see FunctionCreator::normalizeParameters()
     */
    public function createByParameters(FilterApplicator $applicator, IMap $normalizedParameters): callable
    {
        return function ($filterable, FunctionParameter ...$parameters) use ($normalizedParameters, $applicator) {
            return $applicator
                ->applyAll(
                    $this->createFiltersFromParameters($parameters, $normalizedParameters),
                    new Filterable($filterable)
                )
                ->getValue();
        };
    }

    private function assertParameter(mixed $parameter): void
    {
        Assertion::true(
            is_string($parameter) || $parameter instanceof ParameterDefinition || is_array($parameter),
            sprintf(
                'Parameter for function creator must be either string, array or instance of Lmc\ApiFilter\Entity\Parameter but "%s" given.',
                is_object($parameter) ? get_class($parameter) : gettype($parameter)
            )
        );
    }

    /**
     * @param FunctionParameter[] $parameters
     * @param ParameterDefinition[]|IMap $parameterDefinitions IMap<string, Parameter>
     */
    private function createFiltersFromParameters(array $parameters, IMap $parameterDefinitions): FiltersInterface
    {
        $filters = new Filters();
        /** @var ParameterDefinition $definition */
        foreach ($parameterDefinitions as $definition) {
            if ($definition->hasDefaultValue()) {
                $value = $definition->getDefaultValue();
                $title = $definition->getTitleForDefaultValue();
            } else {
                $parameter = $this->getParameterByDefinition($parameters, $definition->getName());
                $value = $parameter->getValue();
                $title = $parameter->getTitle();
            }

            $filter = $this->filterFactory->createFilter($definition->getColumn(), $definition->getFilter(), $value);
            $filter->setFullTitle($title);

            $filters->addFilter($filter);
        }

        return $filters;
    }

    /** @param FunctionParameter[] $parameters */
    private function getParameterByDefinition(array $parameters, string $name): FunctionParameter
    {
        foreach ($parameters as $parameter) {
            if ($parameter->getColumn() === $name) {
                return $parameter;
            }
        }

        throw new InvalidArgumentException(sprintf('Parameter "%s" is required and must have a value.', $name));
    }

    /**
     * @param ParameterDefinition[]|IMap $normalizedParameters IMap<string, Parameter>
     * @return ParameterDefinition[]
     * @see FunctionCreator::normalizeParameters()
     */
    public function getParameterDefinitions(IMap $normalizedParameters): array
    {
        return $normalizedParameters
            ->values()
            ->toArray();
    }
}
