<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Exception;

use Lmc\ApiFilter\AbstractTestCase;

class UnsupportedFilterableExceptionTest extends AbstractTestCase
{
    /**
     * @test
     */
    public function shouldBeCatchableByInstances(): void
    {
        $expectedExceptions = [
            \Throwable::class,
            \Throwable::class,
            \InvalidArgumentException::class,
            ApiFilterExceptionInterface::class,
            InvalidArgumentException::class,
            UnsupportedFilterableException::class,
        ];

        $exception = new UnsupportedFilterableException('message');

        foreach ($expectedExceptions as $expectedException) {
            $this->assertInstanceOf($expectedException, $exception);
        }
    }
}
