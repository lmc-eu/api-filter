<?php declare(strict_types=1);

namespace Lmc\ApiFilter;

use Assert\Assertion as BaseAssertion;
use Lmc\ApiFilter\Exception\InvalidArgumentException;
use MF\Collection\Immutable\ITuple;

class Assertion extends BaseAssertion
{
    protected static $exceptionClass = InvalidArgumentException::class;

    /**
     * Assert that value is instance of ITuple or string value containing a Tuple.
     *
     * @param mixed $value
     * @param string|callable|null $message
     */
    public static function isTuple($value, $message = null, ?string $propertyPath = null): bool
    {
        if (self::isTupleValue($value)) {
            return true;
        }

        $message = sprintf(
            static::generateMessage($message ?: 'Value "%s" is not a Tuple.'),
            static::stringify($value)
        );

        throw static::createException($value, $message, 0, $propertyPath);
    }

    /** @param mixed $value */
    private static function isTupleValue($value): bool
    {
        return $value instanceof ITuple || (is_string($value) && mb_substr($value, 0, 1) === '(');
    }
}
