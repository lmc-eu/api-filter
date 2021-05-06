<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser\FunctionParser;

use Lmc\ApiFilter\Constant\Column;
use MF\Collection\Immutable\Tuple;

class ExplicitFunctionDefinitionByTupleParser extends AbstractFunctionParser
{
    public function supportsParameters(array $queryParameters, string $rawColumn, string|array $rawValue): bool
    {
        return $this->isTuple($rawColumn) && Tuple::parse($rawColumn)->first() === Column::FUNCTION;
    }

    protected function parseParameters(array $queryParameters, string $rawColumn, string|array $rawValue): iterable
    {
        $rawValue = $this->validateTupleValue($rawValue, self::ERROR_FUNCTION_DEFINITION_BY_TUPLE_WITHOUT_TUPLE_VALUES);
        $columns = Tuple::parse($rawColumn)->toArray();
        $values = $this->parseRawValueFromTuple($rawValue, count($columns));

        array_shift($columns);  // just get rid of the first parameter
        $functionName = array_shift($values);

        yield from $this->parseFunction($functionName);
        foreach ($this->functions->getParametersFor($functionName) as $parameter) {
            $index = array_search($parameter, $columns, true);

            yield from $this->parseFunctionParameter($parameter, $values[$index]);
        }
    }
}
