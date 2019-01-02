<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser\FunctionParser;

use Lmc\ApiFilter\Constant\Column;
use MF\Collection\Immutable\Tuple;

class ExplicitFunctionDefinitionByTupleParser extends AbstractFunctionParser
{
    /**
     * @param string|array $rawValue Raw value from query parameters
     */
    public function supportsParameters(array $queryParameters, string $rawColumn, $rawValue): bool
    {
        return $this->isTuple($rawColumn) && Tuple::parse($rawColumn)->first() === Column::FUNCTION;
    }

    /**
     * @param string|array $rawValue Raw value from query parameters
     */
    protected function parseParameters(array $queryParameters, string $rawColumn, $rawValue): iterable
    {
        $rawValue = $this->validateTupleValue($rawValue, 'Function definition by a tuple must have a tuple value.');
        $columns = Tuple::parse($rawColumn)->toArray();
        $values = Tuple::parse($rawValue, count($columns))->toArray();

        array_shift($columns);  // just get rid of the first parameter
        $functionName = array_shift($values);

        yield from $this->parseFunction($functionName);
        foreach ($this->functions->getParametersFor($functionName) as $parameter) {
            $index = array_search($parameter, $columns, true);

            yield from $this->parseFunctionParameter($parameter, $values[$index]);
        }
    }
}
