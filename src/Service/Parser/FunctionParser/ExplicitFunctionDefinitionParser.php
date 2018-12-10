<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser\FunctionParser;

use Lmc\ApiFilter\Assertion;
use MF\Collection\Immutable\Tuple;

class ExplicitFunctionDefinitionParser extends AbstractFunctionParser
{
    /**
     * @param string|array $rawValue Raw value from query parameters
     */
    protected function supportsParameters(array $queryParameters, string $rawColumn, $rawValue): bool
    {
        return !$this->isTuple($rawColumn) && $this->functions->isFunctionRegistered($rawColumn);
    }

    /**
     * @param string|array $rawValue Raw value from query parameters
     */
    protected function parseParameters(array $queryParameters, string $rawColumn, $rawValue): iterable
    {
        if (!$this->functions->isFunctionRegistered($rawColumn)) {
            return;
        }

        yield from $this->parseFunction($rawColumn);

        $parameters = $this->functions->getParametersFor($rawColumn);
        if (count($parameters) === 1) {
            $this->assertSingleStringValue($rawValue);

            yield from $this->parseFunctionParameter(array_shift($parameters), $rawValue);
        } else {
            $rawValue = $this->validateTupleValue($rawValue, 'Explicit function definition must have a tuple value.');

            $values = Tuple::parse($rawValue, count($parameters))->toArray();
            foreach ($parameters as $parameter) {
                yield from $this->parseFunctionParameter($parameter, array_shift($values));
            }
        }
    }

    protected function assertSingleStringValue($rawValue): void
    {
        Assertion::false(
            $this->isTuple($rawValue) || is_array($rawValue),
            'A single parameter function definition must have a single value.'
        );
    }
}
