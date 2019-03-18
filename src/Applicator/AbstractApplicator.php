<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Applicator;

use Lmc\ApiFilter\Entity\Filterable;
use Lmc\ApiFilter\Entity\ParameterDefinition;
use Lmc\ApiFilter\Filter\FilterFunction;
use Lmc\ApiFilter\Filter\FilterInterface;
use Lmc\ApiFilter\Filter\FunctionParameter;

abstract class AbstractApplicator implements ApplicatorInterface
{
    public function getPreparedValue(FilterInterface $filter): array
    {
        $values = $filter->getValue()->getValue();

        return is_iterable($values)
            ? $this->getPreparedMultiValues($filter)
            : $this->getPreparedSingleValue($filter);
    }

    protected function getPreparedMultiValues(FilterInterface $filter): array
    {
        $preparedValues = [];
        $i = 0;
        foreach ($filter->getValue()->getValue() as $value) {
            $preparedValues[$this->createColumnPlaceholder('', $filter, (string) $i++)] = $value;
        }

        return $preparedValues;
    }

    private function createColumnPlaceholder(string $prefix, FilterInterface $filter, string $additional = null): string
    {
        $pieces = [$filter->getTitle()];

        if ($additional !== null) {
            $pieces[] = $additional;
        }

        return $prefix . implode('_', $pieces);
    }

    protected function getPreparedSingleValue(FilterInterface $filter): array
    {
        return [$this->createColumnPlaceholder('', $filter) => $filter->getValue()->getValue()];
    }

    protected function getColumnSinglePlaceholder(string $prefix, FilterInterface $filter): string
    {
        return $this->createColumnPlaceholder($prefix, $filter);
    }

    protected function getColumnMultiPlaceholders(string $prefix, FilterInterface $filter): string
    {
        $values = $filter->getValue()->getValue();

        $placeholders = [];
        $i = 0;
        foreach ($values as $value) {
            $placeholders[] = $this->createColumnPlaceholder($prefix, $filter, (string) $i++);
        }

        return implode(', ', $placeholders);
    }

    /**
     * Apply filter with defined function to filterable and returns the result
     *
     * @example
     * $filter = new FilterFunction(
     *      'fullName',
     *      new Value(function ($filterable, FunctionParameter $firstName, FunctionParameter $surname) {
     *          $filterable = $simpleSqlApplicator->applyFilterWithOperator(
     *              new FilterWithOperator($firstName->getColumn(), $firstName->getValue(), '=', $firstName->getTitle()),
     *              $filterable
     *          );
     *
     *          $filterable = $simpleSqlApplicator->applyFilterWithOperator(
     *              new FilterWithOperator($surname->getColumn(), $surname->getValue(), '=', $surname->getTitle()),
     *              $filterable
     *          );
     *
     *          return $filterable;
     *      })
     * );
     * $sql = 'SELECT * FROM table';
     * $parameters = [
     *      new FunctionParameter('firstName', 'Jon'),
     *      new FunctionParameter('surname', 'Snow'),
     * ]
     *
     * $sql = $simpleSqlApplicator->applyFilterFunction($filter, $sql, $parameters);      // SELECT * FROM table WHERE firstName = :firstName_fun AND surname = :surname_fun
     * $preparedValues = $simpleSqlApplicator->getPreparedValuesForFunction($parameters); // ['firstName_fun' => 'Jon', 'surname_fun' => 'Snow']
     *
     * @param FunctionParameter[] $parameters
     */
    public function applyFilterFunction(FilterFunction $filter, Filterable $filterable, array $parameters): Filterable
    {
        $function = $filter->getValue()->getValue();
        $appliedFilterable = $function($filterable->getValue(), ...$parameters);

        return new Filterable($appliedFilterable);
    }

    /**
     * Prepared values for applied function
     *
     * For example
     * @see applyFilterFunction()
     *
     * @param FunctionParameter[] $parameters
     * @param ParameterDefinition[] $parametersDefinitions
     */
    public function getPreparedValuesForFunction(array $parameters, array $parametersDefinitions = []): array
    {
        return empty($parametersDefinitions)
            ? $this->getPreparedValuesByParameters($parameters)
            : $this->getPreparedValuesByDefinitions($parameters, $parametersDefinitions);
    }

    /**
     * @param FunctionParameter[] $parameters
     */
    private function getPreparedValuesByParameters(array $parameters): array
    {
        $preparedValues = [];
        foreach ($parameters as $parameter) {
            $preparedValues += $this->getPreparedSingleValue($parameter);
        }

        return $preparedValues;
    }

    /**
     * @param FunctionParameter[] $parameters
     * @param ParameterDefinition[] $parametersDefinitions
     */
    private function getPreparedValuesByDefinitions(array $parameters, array $parametersDefinitions): array
    {
        $preparedValues = [];
        $parametersByColumns = $this->getParametersByColumns($parameters);

        foreach ($parametersDefinitions as $definition) {
            $preparedValues += $definition->hasDefaultValue()
                ? [$definition->getTitleForDefaultValue() => $definition->getDefaultValue()->getValue()]
                : $this->getPreparedSingleValue($parametersByColumns[$definition->getName()]);
        }

        return $preparedValues;
    }

    /**
     * @param FunctionParameter[] $parameters
     */
    private function getParametersByColumns(array $parameters): array
    {
        $parametersByColumns = [];
        foreach ($parameters as $parameter) {
            $parametersByColumns[$parameter->getColumn()] = $parameter;
        }

        return $parametersByColumns;
    }
}
