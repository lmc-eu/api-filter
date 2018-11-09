<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Exception;

use Lmc\ApiFilter\AbstractTestCase;
use MF\Collection\Exception\CollectionExceptionInterface;
use MF\Collection\Exception\TupleExceptionInterface;

class TupleExceptionTest extends AbstractTestCase
{
    /**
     * @test
     */
    public function shouldBeCatchableByInstances(): void
    {
        $expectedExceptions = [
            \Exception::class,
            \Throwable::class,
            \InvalidArgumentException::class,
            CollectionExceptionInterface::class,
            TupleExceptionInterface::class,
            ApiFilterExceptionInterface::class,
            InvalidArgumentException::class,
            TupleException::class,
        ];

        $exception = new TupleException('message');

        foreach ($expectedExceptions as $expectedException) {
            $this->assertInstanceOf($expectedException, $exception);
        }
    }
}
