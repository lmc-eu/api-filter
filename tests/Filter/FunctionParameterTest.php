<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Filter;

use Lmc\ApiFilter\AbstractTestCase;
use Lmc\ApiFilter\Entity\Value;

/**
 * @group unit
 */
class FunctionParameterTest extends AbstractTestCase
{
    /**
     * @test
     */
    public function shouldCreateFunctionParameterWithProperDefaults(): void
    {
        $functionParameter = new FunctionParameter('firstName', new Value('Jon'));

        $this->assertSame('firstName_functionParameter', $functionParameter->getTitle());
    }

    /**
     * @test
     */
    public function shouldCreateFunctionParameter(): void
    {
        $functionParameter = new FunctionParameter(
            'firstName',
            new Value('Jon'),
            'first'
        );

        $this->assertSame('firstName', $functionParameter->getColumn());
        $this->assertSame('firstName_first', $functionParameter->getTitle());
        $this->assertSame('Jon', $functionParameter->getValue()->getValue());
    }
}
