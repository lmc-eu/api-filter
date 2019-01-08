<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser\FunctionParser;

use Lmc\ApiFilter\Assertion;
use Lmc\ApiFilter\Constant\Column;
use MF\Collection\Immutable\Tuple;

class FunctionInFilterParameterParser extends AbstractFunctionParser
{
    protected function supportsParameters(array $queryParameters, string $rawColumn, $rawValue): bool
    {
        return array_key_exists(Column::FILTER, $queryParameters);
    }

    /**
     * @param string|array $rawValue Raw value from query parameters
     */
    protected function parseParameters(array $queryParameters, string $rawColumn, $rawValue): iterable
    {
        if ($this->isColumnParsed(Column::FILTER)) {
            return;
        }

        $filterValue = $queryParameters[Column::FILTER];
        Assertion::true(
            is_array($filterValue) || $this->isTuple($filterValue),
            'Filter parameter must have functions in array of in string with Tuple.'
        );
        $this->markColumnAsParsed(Column::FILTER);

        $filters = is_array($filterValue)
            ? $filterValue
            : [$filterValue];

        foreach ($filters as $filter) {
            $this->validateTupleValue($filter, 'All values in filter column must be Tuples.');

            $parsed = Tuple::parse((string) $filter)->toArray();
            $functionName = array_shift($parsed);

            $parameters = $this->functions->getParametersFor($functionName);
            Assertion::count($parsed, count($parameters), 'Given filter must have %s parameters, but %s was given.');

            yield from $this->parseFunction($functionName);

            foreach ($parameters as $parameter) {
                $value = array_shift($parsed);

                yield from $this->parseFunctionParameter($parameter, $value);
            }
        }
    }
}
