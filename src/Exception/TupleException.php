<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Exception;

use MF\Collection\Exception\TupleExceptionInterface;

class TupleException extends InvalidArgumentException implements TupleExceptionInterface
{
    public static function forBaseTupleException(TupleExceptionInterface $e): self
    {
        return new static($e->getMessage(), $e->getCode(), null, null, [], $e);
    }
}
