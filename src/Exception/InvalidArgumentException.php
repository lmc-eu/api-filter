<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Exception;

use Assert\AssertionFailedException;

class InvalidArgumentException extends \InvalidArgumentException implements ApiFilterExceptionInterface, AssertionFailedException
{
    private ?string $propertyPath;
    /** @var mixed */
    private $value;
    private array $constraints;

    /**
     * @param mixed $value
     */
    public function __construct(
        string $message,
        int $code = null,
        ?string $propertyPath = null,
        $value = null,
        array $constraints = null,
        \Throwable $previous = null
    ) {
        parent::__construct($message, (int) $code, $previous);
        $this->propertyPath = $propertyPath;
        $this->value = $value;
        $this->constraints = $constraints ?? [];
    }

    public function getPropertyPath(): ?string
    {
        return $this->propertyPath;
    }

    /** @return mixed */
    public function getValue()
    {
        return $this->value;
    }

    public function getConstraints(): array
    {
        return $this->constraints;
    }
}
