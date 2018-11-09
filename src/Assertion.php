<?php declare(strict_types=1);

namespace Lmc\ApiFilter;

use Assert\Assertion as BaseAssertion;
use Lmc\ApiFilter\Exception\InvalidArgumentException;

class Assertion extends BaseAssertion
{
    protected static $exceptionClass = InvalidArgumentException::class;
}
