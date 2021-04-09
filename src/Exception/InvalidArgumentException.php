<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Exception;

use Assert\AssertionFailedException;

class InvalidArgumentException extends \InvalidArgumentException implements ApiFilterExceptionInterface, AssertionFailedException
{
    public function __construct(
        string $message,
        int $code = null,
        private ?string $propertyPath = null,
        private mixed $value = null,
        private array $constraints = [],
        \Throwable $previous = null
    ) {
        parent::__construct($message, (int) $code, $previous);
    }

    public function getPropertyPath(): ?string
    {
        return $this->propertyPath;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getConstraints(): array
    {
        return $this->constraints;
    }
}
