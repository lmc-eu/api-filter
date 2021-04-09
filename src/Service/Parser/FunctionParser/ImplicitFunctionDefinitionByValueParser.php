<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser\FunctionParser;

use MF\Collection\Mutable\Generic\IMap;

class ImplicitFunctionDefinitionByValueParser extends AbstractFunctionParser
{
    private ?bool $isAllImplicitFunctionDefinitionsChecked;

    public function setCommonValues(
        array $queryParameters,
        IMap $alreadyParsedFunctions,
        IMap $alreadyParsedColumns
    ): void {
        parent::setCommonValues($queryParameters, $alreadyParsedFunctions, $alreadyParsedColumns);
        $this->isAllImplicitFunctionDefinitionsChecked = false;
    }

    /**
     * @param string|array $rawValue Raw value from query parameters
     */
    protected function supportsParameters(array $queryParameters, string $rawColumn, $rawValue): bool
    {
        if ($this->isAllImplicitFunctionDefinitionsChecked) {
            return false;
        }

        foreach ($this->functions->getFunctionNamesByParameter($rawColumn) as $functionName) {
            // - are there all parameters for at least one of the functions?
            foreach ($this->functions->getParametersFor($functionName) as $parameter) {
                if (!array_key_exists($parameter, $queryParameters)) {
                    // check next function
                    continue 2;
                }
            }

            // at least one function has all parameters -> no more searching is required
            return true;
        }

        return false;
    }

    /**
     * @param string|array $rawValue Raw value from query parameters
     */
    protected function parseParameters(array $queryParameters, string $rawColumn, $rawValue): iterable
    {
        if ($this->isAllImplicitFunctionDefinitionsChecked) {
            return;
        }

        $this->isAllImplicitFunctionDefinitionsChecked = true;

        foreach ($queryParameters as $column => $value) {
            if ($this->isColumnParsed($column)) {
                continue;
            }

            foreach ($this->functions->getFunctionNamesByParameter($column) as $functionName) {
                $parameters = $this->functions->getParametersFor($functionName);
                foreach ($parameters as $parameter) {
                    if (!array_key_exists($parameter, $queryParameters)) {
                        // skip all incomplete functions
                        continue 2;
                    }
                }

                yield from $this->parseFunction($functionName);
                foreach ($parameters as $parameter) {
                    yield from $this->parseFunctionParameter($parameter, $queryParameters[$parameter]);
                }
            }
        }
    }
}
