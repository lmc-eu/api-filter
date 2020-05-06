<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser;

use Lmc\ApiFilter\Exception\InvalidArgumentException;

class UnsupportedTupleCombinationParser extends AbstractParser
{
    public function supports(string $rawColumn, $rawValue): bool
    {
        return $this->isTuple($rawColumn) || $this->isTuple($rawValue);
    }

    public function parse(string $rawColumn, $rawValue): iterable
    {
        throw new InvalidArgumentException(sprintf(
            'Invalid combination of a tuple and a scalar. Column %s and value %s.',
            $this->formatForException($rawColumn),
            $this->formatForException($rawValue)
        ));
    }

    /** @param string|string[] $inputValue */
    private function formatForException($inputValue): string
    {
        if (!is_array($inputValue)) {
            return (string) $inputValue;
        }

        $formattedParts = [];
        foreach ($inputValue as $key => $value) {
            $formattedParts[] = is_string($key)
                ? sprintf('%s => %s', $key, $this->formatForException($value))
                : $this->formatForException($value);
        }

        return sprintf('[%s]', implode(', ', $formattedParts));
    }
}
