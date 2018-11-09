<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Exception;

use Lmc\ApiFilter\AbstractTestCase;

class UnsupportedFilterExceptionTest extends AbstractTestCase
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
            ApiFilterExceptionInterface::class,
            InvalidArgumentException::class,
            UnsupportedFilterException::class,
        ];

        $exception = new UnsupportedFilterException('message');

        foreach ($expectedExceptions as $expectedException) {
            $this->assertInstanceOf($expectedException, $exception);
        }
    }
}
