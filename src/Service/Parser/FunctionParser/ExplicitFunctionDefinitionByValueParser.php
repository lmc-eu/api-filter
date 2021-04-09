<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser\FunctionParser;

use Lmc\ApiFilter\Assertion;
use Lmc\ApiFilter\Constant\Column;

class ExplicitFunctionDefinitionByValueParser extends AbstractFunctionParser
{
    protected function supportsParameters(array $queryParameters, string $rawColumn, string|array $rawValue): bool
    {
        if ($this->isColumnParsed(Column::FUNCTION)) {
            return false;
        }

        return array_key_exists(Column::FUNCTION, $queryParameters);
    }

    protected function parseParameters(array $queryParameters, string $rawColumn, string|array $rawValue): iterable
    {
        if ($this->isColumnParsed(Column::FUNCTION)) {
            return;
        }

        $this->markColumnAsParsed(Column::FUNCTION);
        $functionNames = $queryParameters[Column::FUNCTION];

        Assertion::isArray(
            $functionNames,
            'Explicit function definition by values must be an array of functions. %s given.'
        );

        foreach ($functionNames as $functionName) {
            yield from $this->parseFunction($functionName);

            foreach ($this->functions->getParametersFor($functionName) as $parameter) {
                $this->assertParameterExists($queryParameters, $parameter, $functionName);

                yield from $this->parseFunctionParameter($parameter, $queryParameters[$parameter]);
            }
        }
    }

    private function assertParameterExists(array $queryParameters, string $parameter, string $functionName): void
    {
        Assertion::keyExists(
            $queryParameters,
            $parameter,
            sprintf('There is a missing parameter %s for a function %s.', $parameter, $functionName)
        );
    }
}
