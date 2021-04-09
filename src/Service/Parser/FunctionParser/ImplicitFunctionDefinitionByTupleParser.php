<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser\FunctionParser;

use MF\Collection\Immutable\Tuple;

class ImplicitFunctionDefinitionByTupleParser extends AbstractFunctionParser
{
    protected function supportsParameters(array $queryParameters, string $rawColumn, string|array $rawValue): bool
    {
        if ($this->isTuple($rawColumn)) {
            $possibleParameters = Tuple::parse($rawColumn)->toArray();

            foreach ($this->functions->getFunctionNamesByAllParameters($possibleParameters) as $functionName) {
                return true;
            }
        }

        return false;
    }

    protected function parseParameters(array $queryParameters, string $rawColumn, string|array $rawValue): iterable
    {
        $rawValue = $this->validateTupleValue($rawValue, self::ERROR_FUNCTION_DEFINITION_BY_TUPLE_WITHOUT_TUPLE_VALUES);
        $columns = Tuple::parse($rawColumn)->toArray();
        $values = $this->parseRawValueFromTuple($rawValue, count($columns));

        foreach ($this->functions->getFunctionNamesByAllParameters($columns) as $functionName) {
            yield from $this->parseFunction($functionName);
        }

        foreach ($columns as $parameter) {
            yield from $this->parseFunctionParameter($parameter, array_shift($values));
        }
    }
}
