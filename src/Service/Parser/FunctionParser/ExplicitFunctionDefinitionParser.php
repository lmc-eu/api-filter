<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser\FunctionParser;

use Lmc\ApiFilter\Assertion;

class ExplicitFunctionDefinitionParser extends AbstractFunctionParser
{
    protected function supportsParameters(array $queryParameters, string $rawColumn, string|array $rawValue): bool
    {
        return !$this->isTuple($rawColumn) && $this->functions->isFunctionRegistered($rawColumn);
    }

    protected function parseParameters(array $queryParameters, string $rawColumn, string|array $rawValue): iterable
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

            $values = $this->parseRawValueFromTuple($rawValue, count($parameters));
            foreach ($parameters as $parameter) {
                yield from $this->parseFunctionParameter($parameter, array_shift($values));
            }
        }
    }

    protected function assertSingleStringValue(string|array $rawValue): void
    {
        Assertion::false(
            $this->isTuple($rawValue) || is_array($rawValue),
            'A single parameter function definition must have a single value.'
        );
    }
}
