<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service;

use Lmc\ApiFilter\AbstractTestCase;
use Lmc\ApiFilter\Entity\Value;
use Lmc\ApiFilter\Escape\EscapeString;

class EscapeServiceTest extends AbstractTestCase
{
    /** @var EscapeService */
    private $escapeService;

    protected function setUp(): void
    {
        $this->escapeService = new EscapeService();
    }

    /**
     * @test
     * @dataProvider provideAnyValue
     *
     * @param mixed $value
     */
    public function shouldNotSupportAnyEscapeByDefault($value): void
    {
        $result = $this->escapeService->supports('column', new Value($value));

        $this->assertFalse($result);
    }

    public function provideAnyValue(): array
    {
        return [
            // value
            'null' => [null],
            'string' => ['string'],
            'int' => [1],
            'float' => [1.2],
            'bool' => [true],
        ];
    }

    /**
     * @test
     * @dataProvider provideStringsToEscape
     */
    public function shouldEscapeString(string $string, string $expected): void
    {
        $column = 'column';
        $value = new Value($string);
        $this->escapeService->addEscape(new EscapeString(), 1);

        $supports = $this->escapeService->supports($column, $value);
        $this->assertTrue($supports);

        $result = $this->escapeService->escape($column, $value)->getValue();
        $this->assertSame($expected, $result);
    }

    public function provideStringsToEscape(): array
    {
        return [
            // string, expected
            'empty' => ['', '\'\''],
            'one word' => ['word', '\'word\''],
        ];
    }

    /**
     * @test
     */
    public function shouldThrowLogicExceptionOnEscapingUnsupportedValue(): void
    {
        $column = 'column';
        $value = new Value('value');

        $supports = $this->escapeService->supports($column, $value);
        $this->assertFalse($supports);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('You must call supports method first so there will be at least one escape for value "\'value\'".');

        $this->escapeService->escape($column, $value);
    }
}
